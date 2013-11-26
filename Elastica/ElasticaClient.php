<?php 
namespace Majes\SearchBundle\Elastica;

use FOS\ElasticaBundle\Client as BaseClient;

use Elastica\Exception\ExceptionInterface;
use Elastica\Response;

class ElasticaClient extends BaseClient
{
    public function request($path, $method = 'GET', $data = array(), array $query = array())
    {
        try {
        	return parent::request($path, $method, $data, $query);
        } catch (ExceptionInterface $e) {
        	return new \Elastica\Response('{"took":0,"timed_out":false,"hits":{"total":0,"max_score":0,"hits":[]}, "status": "down"}');
       	}
    }
}