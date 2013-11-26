<?php 
namespace Majes\SearchBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Majes\CoreBundle\Controller\SystemController;

use Elastica\Document;
use Elastica\Facet\Query as FacetQuery;
use Elastica\Query;
use Elastica\Type;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;
use Elastica\Client;
use Pagerfanta\Pagerfanta;

class ControllerListener
{

    private $_container;

    public function __construct(Container $container)
    {
        $this->_container = $container;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        /*
         * $controller peut être une classe ou une closure. Ce n'est pas
         * courant dans Symfony2 mais ça peut arriver.
         * Si c'est une classe, elle est au format array
         */
        $controllerObject = $controller[0];
        if (!is_array($controller)) {
            return;
        }

        if ($controllerObject instanceof SystemController) {

            $query = '*';
            $filters = null;
    
            $index = $this->_container->get('fos_elastica.index.majesteel_back');
            $finder = $this->_container->get('fos_elastica.finder.majesteel_back');
    
            // Define a Query. We want a string query.
            $elasticaQueryString = new \Elastica\Query\QueryString();
    
            $elasticaQueryString->setQuery($query);
    
            // Create the actual search object with some data.
            $elasticaQuery = new \Elastica\Query();
            $elasticaQuery->setQuery($elasticaQueryString);
    
            // Pagination
            $elasticaQuery->setFrom(0);    // Where to start?
            $elasticaQuery->setLimit(1);   // How many?
                
            // Search and get facets from elasticaResults
            $elasticaSearch = $index->search($elasticaQuery);
            $data = $elasticaSearch->getResponse()->getData();

            if(isset($data['status']) && $data['status'] == 'down'){
                $notification = $this->_container->get('majes.notification');

                $notification->set(array('_source' => 'search'));
                $notification->reinit();
                
                $notification->add('notices', array('status' => 'danger', 'title' => 'Elasticasearch is down', 'url' => 'http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/setup.html', 'target' => '_blank'));
            
            }
        }
    }
}