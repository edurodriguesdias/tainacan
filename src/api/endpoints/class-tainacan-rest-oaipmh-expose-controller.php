<?php

namespace Tainacan\API\EndPoints;

use Tainacan\OAIPMHExpose;
use \Tainacan\API\REST_Controller;

class REST_Oaipmh_Expose_Controller extends REST_Controller {

    /**
     * REST_Facets_Controller constructor.
     */
    public function __construct() {
        $this->rest_base = 'oai';
        parent::__construct();
        add_action('init', array(&$this, 'init_objects'), 11);
    }

    /**
     * Initialize objects after post_type register
     */
    public function init_objects() {
        $this->controller_oai = new \Tainacan\OAIPMHExpose\OAIPMH_Expose();
        $this->list_sets = new \Tainacan\OAIPMHExpose\OAIPMH_List_Sets();
        $this->list_metadata_formats = new \Tainacan\OAIPMHExpose\OAIPMH_List_Metadata_Formats();
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_verb'),
                'permission_callback' => array($this, 'get_verb_permissions_check')
            )
        ));
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return bool|\WP_Error
     */
    public function get_verb_permissions_check( $request ) {
        return true;
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_REST_Response
     * @throws \Exception
     */
    public function get_verb( $request ){
        $verb = ( isset($request['verb']) ) ? $request['verb'] : false;

        switch ($verb){

            case 'ListSets':
                $allowed_arguments = array('verb','resumptionToken');
                foreach ($request as $key => $value) {
                    if(!in_array($key, $allowed_arguments)){
                        $this->list_sets->config();
                        $this->list_sets->errors[] =  $this->list_sets->oai_error('badArgument');
                        $this->list_sets->oai_exit($request, $this->list_sets->errors);
                    }
                }
                $this->list_sets->list_sets($request);
                break;

            case 'ListMetadataFormats':
                $this->list_metadata_formats->list_metadata_formats($request);
                break;

            default:
                $this->controller_oai->config();
                $this->controller_oai->errors[] = $this->controller_oai->oai_error('noVerb');
                $this->controller_oai->oai_exit( $request, $this->controller_oai->errors);
                break;
        }

        die;
    }
}