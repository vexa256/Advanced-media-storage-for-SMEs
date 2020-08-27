<?php namespace Common\Core\Controllers;

use Common\Core\AppUrl;
use Common\Core\BaseController;
use Common\Core\Bootstrap\BootstrapData;
use Common\Settings\Settings;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomeController extends BaseController {

    /**
     * @var BootstrapData
     */
    private $bootstrapData;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param BootstrapData $bootstrapData
     * @param Settings $settings
     */
    public function __construct(BootstrapData $bootstrapData, Settings $settings)
    {
        $this->bootstrapData = $bootstrapData;
        $this->settings = $settings;
    }

    /**
	 * @return View|Response
	 */
	public function show()
	{
	    // only get meta tags if we're actually
        // rendering homepage and not a fallback route
	    if (request()->route()->uri === '/' && $response = $this->handleSeo()) {
            return $response;
        }

        return response(
            view('app')
                ->with('bootstrapData', $this->bootstrapData->init())
                ->with('htmlBaseUri', app(AppUrl::class)->htmlBaseUri)
                ->with('settings', $this->settings)
                ->with('customHtmlPath', public_path('storage/custom-code/custom-html.html'))
                ->with('customCssPath', public_path('storage/custom-code/custom-styles.css'))
        );
	}
}
