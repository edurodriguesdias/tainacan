<?php

namespace Tainacan\OAIPMHExpose;
use Tainacan\Repositories;
use Tainacan\Entities;

class OAIPMH_List_Metadata_Formats extends OAIPMH_Expose {

    /** \var $working_node
     * O noh que esta sendo utilizado atualmente
     */
    protected $working_node;
    public $errors;
    public $xml_creater;
    public $restoken = '-';
    public $expirationdatetime;
    public $num_rows;
    public $cursor;
    public $deliveredrecords;
    public $from;
    public $until;
    public $sets;
    public $metadataPrefix;

    /**
     * @signature - list_metadata_formats
     * @author: Eduardo
     */
    public function list_metadata_formats( $data ) {
        session_write_close();
        $formats = array();

        $this->config();
        $this->xml_creater = new Xml_Response($data);

        if( isset($data['identifier']) ){// se estiver olhando por metadados de um unico objeto
            $item_id = str_replace('oai:'.$this->repositoryIdentifier.':','', $data['identifier']);
            $item = new \Tainacan\Entities\Item($item_id);

            if( $item->get_id() && $item->get_status() =='publish' ){
                $formats = $this->get_metadata_formats($item->get_id());
                if(empty($formats)){
                    $this->errors[] = $this->oai_error('noMetadataFormats');
                    $this->oai_exit($data,$this->errors);
                }
            }else{
                $this->errors[] = $this->oai_error('idDoesNotExist');
                $this->oai_exit($data,$this->errors);
            }
        }

        foreach ( $this->get_metadata_formats() as $metadata_format) {
            if(!empty($formats)&&!in_array($metadata_format['metadataPrefix'], $formats)){
                continue;
            }
            $description_node = $this->xml_creater->add2_verbNode('metadataFormat');
            $this->xml_creater->addChild($description_node, 'metadataPrefix', $metadata_format);
            //$this->xml_creater->addChild($description_node, 'schema', $metadata_format['schema']);
            //$this->xml_creater->addChild($description_node, 'metadataNamespace', $metadata_format['metadataNamespace']);
        }

        header($this->CONTENT_TYPE);

        if (isset($this->xml_creater)) {
            $this->xml_creater->display();
        } else {
            exit("There is a bug in codes");
        }
    }

}