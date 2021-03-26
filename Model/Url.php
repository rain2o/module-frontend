<?php
declare(strict_types=1);
/**
 * Frontend URL Model
 *
 * PHP version 7
 *
 * @category  Rain2o
 * @package   Rain2o_Frontend
 * @author    Joel Rainwater <joel@rainwater.io>
 * @copyright 2021 Joel Rainwater
 * @license   https://opensource.org/licenses/osl-3.0.php OSL 3.0
 */
namespace Rain2o\Frontend\Model;

use Magento\Framework\UrlInterface;

class Url extends \Magento\Framework\Url implements UrlInterface
{
    /**
     * Path to frontend base_url config value
     */
    const FE_URL_PATH = "web/frontend/base_url";

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * Retrieve Base URL with store code
     *
     * @param array $params
     * @return string
     */
    public function getBaseUrl($params = [])
    {
        /**
         *  Original Scope
         */
        $this->origScope = $this->_getScope();

        if (isset($params['_scope'])) {
            $this->setScope($params['_scope']);
        }

        // CUSTOM CODE
        // we only want to override if we're in frontend
        if ($this->_getScope()->getCode() === 'admin') {
            $this->isAdmin = true;
            return parent::getBaseUrl($params);
        } else {
            $this->isAdmin = false;
        }
        // END CUSTOM CODE

        if (isset($params['_type'])) {
            $this->getRouteParamsResolver()->setType($params['_type']);
        }

        if (isset($params['_secure'])) {
            $this->getRouteParamsResolver()->setSecure($params['_secure']);
        }

        /**
         * Add availability support urls without scope code
         */
        if ($this->_getType() == UrlInterface::URL_TYPE_LINK
            && $this->_getRequest()->isDirectAccessFrontendName(
                $this->_getRouteFrontName()
            )
        ) {
            $this->getRouteParamsResolver()->setType(UrlInterface::URL_TYPE_DIRECT_LINK);
        }

        // CUSTOM CODE
        // remove slash so we can add one and know it's only one
        $result = rtrim($this->_getConfig(self::FE_URL_PATH), "/") . "/";
        // add store code
        $result .= $this->_getScope()->getCode() . "/";
        // END CUSTOM CODE

        // setting back the original scope
        $this->setScope($this->origScope);
        $this->getRouteParamsResolver()->setType(self::DEFAULT_URL_TYPE);

        return $result;
    }

    /**
     * Retrieve route URL
     *
     * @param string $routePath
     * @param array $routeParams
     * @return string
     */
    public function getRouteUrl($routePath = null, $routeParams = null)
    {
        // get our new base URL for frontend
        $base = $this->getBaseUrl($routeParams);

        // use parent if we're in admin scope
        if ($this->isAdmin) {
            return parent::getRouteUrl($routePath, $routeParams);
        }

        // route mapping happens here
        $this->_setRoutePath($routePath);

        // use our base url and the mapped route path
        $frontUrl = $base . $this->_getRoutePath($routeParams);

        return $frontUrl;
    }

    /**
     * Set Route Parameters
     *
     * @param string $data
     * @return \Magento\Framework\UrlInterface
     */
    protected function _setRoutePath($data)
    {
        // kept from original
        if ($this->_getData('route_path') == $data) {
            return $this;
        }

        $this->unsetData('route_path');
        $routePieces = explode('/', $data);
        
        // additional logic here if needed to map route path to frontend routes
        $pieces = $this->yourFunctionForMappingRoutes($routePieces);

        $this->setData('route_path', implode("/", $pieces));
        return $this;
    }

    /**
     * Retrieve route path
     *
     * @param array $routeParams
     * @return string
     */
    protected function _getRoutePath($routeParams = [])
    {
        // use parent function if we're in admin scope
        if ($this->isAdmin) {
            return parent::_getRoutePath($routeParams);
        }
        return $this->_getData('route_path');
    }
}
