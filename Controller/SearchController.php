<?php

namespace Majes\SearchBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Response;
use Majes\CoreBundle\Controller\SystemController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Elastica\Document;
use Elastica\Facet\Query as FacetQuery;
use Elastica\Query;
use Elastica\Type;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;
use Elastica\Client;
use Pagerfanta\Pagerfanta;

class SearchController extends Controller implements SystemController {

    public function searchAction() {
        $request = $this->getRequest();
        $query = $request->get('query', '*');
        $filters = $request->get('filters', null);
        
        $routeName = $request->get('_route');

        if($routeName == '_search_admin'){
            $index = $this->container->get('fos_elastica.index.majesteel_back');
            $finder = $this->container->get('fos_elastica.finder.majesteel_back');
        }else{
            $index = $this->container->get('fos_elastica.index.majesteel_front');
            $finder = $this->container->get('fos_elastica.finder.majesteel_front');
        }

        // Define a Query. We want a string query.
        $elasticaQueryString = new \Elastica\Query\QueryString();

        $elasticaQueryString->setQuery($query);

        // Create the actual search object with some data.
        $elasticaQuery = new \Elastica\Query();
        $elasticaQuery->setQuery($elasticaQueryString);

        // Pagination
        $elasticaQuery->setFrom(0);    // Where to start?
        $elasticaQuery->setLimit(50);   // How many?
        
        // Define a new facet.
        $elasticaFacet = new \Elastica\Facet\Terms('tags');
        $elasticaFacet->setField('tags');
        $elasticaFacet->setSize(10);
        $elasticaFacet->setOrder('reverse_count');

        // Add that facet to the search query object.
        $elasticaQuery->addFacet($elasticaFacet);

        // Search and get facets from elasticaResults
        $elasticaFacets = $index->search($elasticaQuery)->getFacets();

        // Add filters
        if (!is_null($filters)) {
            $elasticaFilterOr = new \Elastica\Filter\BoolOr();
            foreach ($filters as $filterName => $filter) {
                $elasticaFilter = new \Elastica\Filter\Term();
                $elasticaFilter->setTerm('tags', $filterName);

                $elasticaFilterOr->addFilter($elasticaFilter);
            }

            // Set filters
            $elasticaQuery->setFilter($elasticaFilterOr);
        }

        //Search on the finder.
        $elasticaResultSet = $finder->find($elasticaQuery);

        if($routeName == '_search_admin')
            return $this->render('MajesSearchBundle:Search:results.html.twig', array(
                    'results' => $elasticaResultSet,
                    'facets' => $elasticaFacets,
                    'query' => $query,
                    'filters' => $filters,
                    'pageTitle' => 'Search'));
        else{
            if($this->get('templating')->exists('MajesTeelBundle:Search:results.html.twig'))
                $template_twig = 'MajesTeelBundle:Search:results.html.twig';
            else
                $template_twig = 'MajesSearchBundle:Search:results.html.twig';
            
            return $this->render($template_twig, array(
                    'results' => $elasticaResultSet,
                    'facets' => $elasticaFacets,
                    'query' => $query,
                    'filters' => $filters,
                    'pageTitle' => 'Search'));
        }

    }
}
