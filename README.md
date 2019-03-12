Os2Display Slide Tools

Tools for working with slides for [https://github.com/os2display](https://github.com/os2display) The tools are meant to help with slides that have "slides within". It is not meant to replace the slide advancer in Os2Display, but it can allow a slide setup once by a person to have as many sub-slides as needed.

The idea is that a slide will have a number of "data items". That could be a list of events for instance. Each event would be a data item. The slide can have any number of subslides that displays a number of data items. The variables here are the settings available:

### Config variables

| Variable name           |                                                              |
| ----------------------- | ------------------------------------------------------------ |
| `sis_total_items`       | How many data items should the slide have.                   |
| `sis_items_pr_slide`    | How many data items should be displayed on each subslide.    |
| `sis_subslide_duration` | How long should each subslide be displayed.                  |
| `sis_cron_subscriber`   | Identifier to use if you want your slide type to fetch data on cron. |



## Creating a new slide type

In your [custom bundle](https://github.com/os2display/docs/blob/master/guidelines/custom-bundles-guidelines.md), create a new [template](https://github.com/os2display/docs/blob/master/guidelines/template-guidelines.md) following the documentation [here](https://github.com/os2display/docs/blob/master/guidelines/template-guidelines.md).

To take advantage of the tools in this repo you need to do a couple of things in your `.json` file:

- In the `empty_options` part of the config, add the following defaults (with your own values of course).

  ```json
    "empty_options": {
      "sis_cron_subscriber": "your_cron_key",
      "sis_subslide_duration": 10,
      "sis_total_items": 9,
      "sis_items_pr_slide": 1
    }
  ```

* If you want the user to be able to adjust these values, add the tool from this library in the `tools` part:

```javascript
    "tools": [   {
        "name": "Slides in slides",
        "id": "slides-in-slide-config-editor"
      }
    ]
```

The `.js`-file you link to in `paths.js` in the `.json.` file can take advantage of the sub-slide-advancing in this tool.

The Os2Display framework does not have a way to include more than one JS file for each slide, so you will have to use Gulp or whatever your tool of choice is to compile `Resources/public/js/slides-in-slide.js` from this library into your `.js` file for the slide.

You can then use the subslide advancement something like this:

```javascript
// Register the function, if it does not already exist.
if (!window.slideFunctions['my-template-id']) {
  window.slideFunctions['my-template-id'] = {
    /**
     * Setup the slide for rendering.
     * @param scope
     *   The slide scope.
     */
    setup: function setupMyTemplate(scope) {
      // Get subslides (that is the data items), num_subslides, and slide_duration
      // and call the slides-in-slides tool with those values.
      window.slidesInSlides.setup(scope, subslides, num_subslides, slide_duration);
    },

    /**
     * Run the slide.
     *
     * @param slide
     *   The slide.
     * @param region
     *   The region to call when the slide has been executed.
     */
    run: function runMyTemplate(slide, region) {
      window.slidesInSlides.run(slide, region);
    }
  };
}
```

## Fetching data on Cron

Create a service in your bundle that implements Symfony's `EventSubscriberInterface`. In the `getSubscribedEvents` function, use something like this:

```javascript
  public static function getSubscribedEvents()
  {
    return [
      'os2displayslidetools.sis_cron.your_cron_key' => [
        ['myFunctionToGetData'],
      ]
    ];
  }
```



See [this bundle](https://github.com/kkos2/os2display-admin) for some examples on how to use this tool with cron.