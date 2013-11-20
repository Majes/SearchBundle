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

    public function filteredSearchAction() {
        $request = $this->getRequest();
        $queryString = $request->get('query');
        $filters = $request->get('filters');
        
        $finder = $this->container->get('fos_elastica.finder.majesteel_back');

        $query = new \Elastica\Query\QueryString('admin');
        
        $term = new \Elastica\Filter\Term(array('role', true));

        $filteredQuery = new \Elastica\Query\Filtered($query, $term);
        $resultSet = $finder->find($filteredQuery);
        
        var_dump($resultSet);
        exit;
        
        return $this->render('MajesSearchBundle:Search:ajax/list-results.html.twig', array(
                    'results' => $resultSet));
    }

    public function searchAction() {
        $request = $this->getRequest();
        $query = $request->get('query');
        
        $index = $this->container->get('fos_elastica.index.majesteel_back');
        $finder = $this->container->get('fos_elastica.finder.majesteel_back');

        //QUERY :
        $elasticaQuery = new \Elastica\Query();
        $elasticaQueryString = new \Elastica\Query\QueryString();
        $elasticaQueryString->setQuery($query);
        $elasticaQuery->setQuery($elasticaQueryString);

        //FACETS
        $elasticaFacet = new \Elastica\Facet\Terms('tags');
        $elasticaFacet->setField('tags');
        
        

        $elasticaQuery->addFacet($elasticaFacet);

        // ResultSet
        $resultSet = $finder->find($elasticaQuery);
        $facets = $index->search($elasticaQuery)->getFacets();

        return $this->render('MajesSearchBundle:Search:results.html.twig', array(
                    'results' => $resultSet,
                    'facets' => $facets,
                    'pageTitle' => 'Search'));
    }

}
