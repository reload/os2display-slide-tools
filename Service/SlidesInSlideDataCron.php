<?php

namespace Reload\Os2DisplaySlideTools\Service;

use Os2Display\CoreBundle\Events\CronEvent;
use Reload\Os2DisplaySlideTools\Slides\SlidesInSlide;
use Reload\Os2DisplaySlideTools\Events\SlidesInSlideEvent;

class SlidesInSlideDataCron {

  /**
   * @var \Symfony\Bridge\Monolog\Logger $logger
   */
  private $logger;
  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  private $container;

  public function __construct($container)
  {
    $this->container = $container;
    $this->logger = $this->container->get('logger');
  }

  public function onCron(CronEvent $event)
  {
    $slideRepo = $this->container->get('doctrine')->getRepository('Os2DisplayCoreBundle:Slide');
    $slidesOurType = $slideRepo->findBySlideType('slides-in-slide');

    foreach ($slidesOurType as $slide) {

      $slidesInSlide = new SlidesInSlide($slide);


      $slideEvent = new SlidesInSlideEvent($slidesInSlide);
      $subscriberName = 'os2displayslidetools.sis_cron.' . $slidesInSlide->getOption('sis_cron_subscriber');
      $subslides = $this->container->get('event_dispatcher')->dispatch($subscriberName, $slideEvent)->getSubSlides();

      if (!is_array($subslides)) {
        $this->logger->addError("Couldn't find event subscriber for : " . $subscriberName);
        continue;
      }

      if (count($subslides) < 1) {
        $this->logger->addError("Found no data for slide with id: " . $slide->getId());
        continue;
      }

      $subslidesPrSlide = $slidesInSlide->getOption('sis_items_pr_slide', 3);
      $slides = ($subslidesPrSlide > 1) ? array_chunk($subslides, $subslidesPrSlide) : $subslides;
      try {
        $slide->setExternalData([
          'sis_data_slides' => $slides,
          'sis_data_num_slides' => count($slides),
          'sis_data_items_pr_slide' => $subslidesPrSlide,
        ]);
        // Write to the db.
        $entityManager = $this->container->get('doctrine')->getManager();
        $entityManager->flush();
      } catch (\Exception $O_o) {
        $this->logger->error('An error occured trying save data on slides in slide: ' . $O_o->getMessage());
      }
    }
  }
  
}