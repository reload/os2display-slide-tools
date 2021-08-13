<?php

namespace Reload\Os2DisplaySlideTools\Service;

use Doctrine\ORM\EntityManager;
use Os2Display\CoreBundle\Entity\Slide;
use Os2Display\CoreBundle\Events\CronEvent;
use Psr\Log\LoggerInterface;
use Reload\Os2DisplaySlideTools\Events\SlidesInSlideEvent;
use Reload\Os2DisplaySlideTools\Slides\SlidesInSlide;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SlidesInSlideDataCron {

  /**
   * @var \Doctrine\ORM\EntityManager $entityManager
   */
  private $entityManager;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * @var \Psr\Log\LoggerInterface $logger
   */
  private $logger;

  /**
   * Whether or not to use a time to live for external data on slides.
   *
   * @var bool $useTtl
   */
  private $useTtl;

  public function __construct(EntityManager $entityManager, EventDispatcherInterface $dispatcher, LoggerInterface $logger, $useTtl)
  {
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
    $this->logger = $logger;
    $this->useTtl = $useTtl;
  }

  public function onCron(CronEvent $event)
  {
    $slidesOurType = $this->entityManager
      ->getRepository('Os2DisplayCoreBundle:Slide')
      ->findBySlideType('slides-in-slide');

    /** @var \Os2Display\CoreBundle\Entity\Slide $slide */
    foreach ($slidesOurType as $slide) {

      /**
       * We are running into issues, when we try to fetch data for more than ~25 slides in quick succession
       * during a cron run. This issued is specifically caused by the kk.dk multisite system which drops 
       * connections in such situations - probably to protect against DDoS attacks.
       * To avoid triggering this we added a tiny sleep delay between each call. It may not be the most 
       * efficient solution but it works for now.
       */
      sleep(0.5);
      $this->logger->addInfo("\nID: {$slide->getId()} ---------------------------------------------------------------------- \n");

      $slidesInSlide = new SlidesInSlide($slide);

      if (!$this->shouldFetchData($slide)) {
        continue;
      }

      $slideEvent = new SlidesInSlideEvent($slidesInSlide);
      $subscriberName = 'os2displayslidetools.sis_cron.' . $slidesInSlide->getOption('sis_cron_subscriber');

      $subslides = [];
      // Fetch subslides by delegating the actual business implementation done
      // in the concrete slides cron listeners.
      // OS2Display core halts any cron-processing if an exception escapes from
      // a cron worker, so we catch and log anything that escapes.
      try {
        $subslides = $this->dispatcher->dispatch($subscriberName, $slideEvent)->getSubSlides();
      }
      catch (\Exception $e) {
        $this->logger->error('An error occurred trying to fetch subslides for slide ' . $slide->getId() . ': ' . $e->getMessage());
        continue;
      }

      if (!is_array($subslides)) {
        $this->logger->addError("Couldn't find event subscriber for : " . $subscriberName);
        continue;
      }

      if (empty($subslides)) {
        $this->logger->addError("Found no data for slide with id: " . $slide->getId());
      }

      $subslidesPrSlide = $slidesInSlide->getOption('sis_items_pr_slide', 3);
      $slides = ($subslidesPrSlide > 1) ? array_chunk($subslides, $subslidesPrSlide) : $subslides;
      try {
        $slide->setExternalData([
          'sis_data_slides' => $slides,
          'sis_data_num_slides' => count($slides),
          'sis_data_items_pr_slide' => $subslidesPrSlide,
        ]);
        // Note when we fetched the data.
        $slidesInSlide->setOption('sis_data_last_fetch', time());
        // Write to the db.
        $this->entityManager->flush();
      } catch (\Exception $O_o) {
        $this->logger->error('An error occured trying save data on slides in slide: ' . $O_o->getMessage());
      }
    }
  }

  /**
   * Check if it is necessary to fetch fresh data for the slide.
   *
   * @param Slide $slide
   *
   * @return bool
   */
  private function shouldFetchData(Slide $slide) {
    $options = $slide->getOptions();

    $dataTtlMinutes = $options['sis_data_ttl_minutes'] ?? 10;
    // If TTL is disabled or the TTL is set to 0 on a slide, then always fetch
    // data.
    if (!$this->useTtl || empty($dataTtlMinutes)) {
      return true;
    }

    $lastFetch = $options['sis_data_last_fetch'] ?? 0;
    // If the slide has just been saved, then fetch data again.
    if ($slide->getModifiedAt() > $lastFetch) {
      return true;
    }

    $now = time();
    $dataTtl = $dataTtlMinutes * 60;

    return $now > ($lastFetch + $dataTtl);
  }
  
}
