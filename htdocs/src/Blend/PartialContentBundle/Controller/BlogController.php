<?php
namespace Blend\PartialContentBundle\Controller;

use Symfony\Component\HttpFoundation\Response,
    eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController as APIViewController,
    eZ\Publish\API\Repository\Values\Content,
    eZ\Publish\API\Repository\Values\Content\Relation,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion,
    eZ\Publish\API\Repository\Values\Content\Search\SearchResult,
    eZ\Publish\API\Repository\Values\Content\Query\SortClause,
    ezcFeed;

/**
 * BlogController provides basic sub-request methods used by the Partial Content Blog
 */
class BlogController extends APIViewController
{

    public function postsInSeries($seriesId, $contentId, $viewType='line')
    {
        $contentService = $this->getRepository()->getContentService();
        $locationService = $this->getRepository()->getLocationService();

        $seriesObject = $contentService->loadContent($seriesId);
        $seriesInfo = $contentService->loadContentInfo($seriesId);

        $object = $contentService->loadContentInfo($contentId);

        $postIds = $seriesObject->getFieldValue('posts')->destinationContentIds;

        $posts = array();

        foreach ($postIds as $id) {
            $contentInfo = $contentService->loadContentInfo($id);
            $posts[] = $locationService->loadLocation($contentInfo->mainLocationId);
        }

        //$seriesLists[]=array('series'=>$singleSeries,'posts'=>$posts);

        $response = $this->buildResponse(
            __METHOD__ . $contentId,
            $object->modificationDate
        );

        return $this->render(
            'BlendPartialContentBundle::series_list.html.twig',
            array(
                'series'=>$seriesInfo,
                'posts'=>$posts,
                'content'=>$object,
                'viewType'=>$viewType
            ),
            $response
        );

    }

    /**
     * postsByDate returns a formatted list of all posts beneath a location id(aka node id)
     * Posts are retrieved from the repository and returned in reverse chronological order
     * @param $subTreeLocationId The location ID (node ID) to look under
     * @param string $viewType What type of view template should render each result
     * @param int $limit How many items to return
     * @param int $offset The record offset to start at
     * @param bool $navigator Whether to render a paginator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postsByDate($subTreeLocationId, $viewType='summary', $limit=5, $offset=0, $navigator=true)
    {
        //Retrieve the location service from the Symfony container
        $locationService = $this->getRepository()->getLocationService();

        //Load the called location (node) from the repository based on the ID
        $root = $locationService->loadLocation( $subTreeLocationId );

        //Get the modification time from the content object
        $modificationDate = $root->contentInfo->modificationDate;

        //Retrieve a subtree fetch of the latest posts
        $postResults = $this->fetchSubTree(
            $root,
            array('blog_post'),
            array(new SortClause\Field('blog_post','publication_date',Query::SORT_DESC)),
            true,
            $limit,
            $offset
        );

        //Convert the results from a search result object into a simple array
        $posts = array();
        foreach ( $postResults->searchHits as $hit )
        {

            $posts[] = $hit->valueObject;

            //If any of the posts is newer than the root, use that post's modification date
            if ($hit->valueObject->contentInfo->modificationDate > $modificationDate) {
                $modificationDate = $hit->valueObject->contentInfo->modificationDate;
            }
        }

        //Set the etag and modification date on the response
        $response = $this->buildResponse(
            __METHOD__ . $subTreeLocationId,
            $modificationDate
        );

        $response->headers->set( 'X-Location-Id', $subTreeLocationId );

        //If nothing has been modified, return a 304
        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        //Render the output
        return $this->render(
            'BlendPartialContentBundle::posts_list.html.twig',
            array(
                'total' => $postResults->totalCount,
                'offset' => $offset,
                'root' => $root,
                'paginationRoot' => $root,
                'posts' => $posts,
                'viewType' => $viewType,
                'navigator' => (bool) $navigator,
                'limit' => $limit,
                'next' => 'Older',
                'prev' => 'Newer'
            ),
            $response
        );
    }

    public function feature($feature_id=0, $locationId, $subTreeLocationId){
         //Retrieve the location service from the Symfony container
        $locationService = $this->getRepository()->getLocationService();
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );

        //Load the called location (node) from the repository based on the ID
        $root = $locationService->loadLocation( $subTreeLocationId );

        //Get the modification time from the content object
        $modificationDate = $root->contentInfo->modificationDate;

        //Retrieve a subtree fetch of the latest posts
        $postResults = $this->fetchSubTree(
            $root,
            array('blog_post'),
            array(new SortClause\Field('blog_post','publication_date',Query::SORT_DESC)),
            true
        );

        //Convert the results from a search result object into a simple array
        $posts = array();
        foreach ( $postResults->searchHits as $hit )
        {
            $posts[] = $hit->valueObject;

            //If any of the posts is newer than the root, use that post's modification date
            if ($hit->valueObject->contentInfo->modificationDate > $modificationDate) {
                $modificationDate = $hit->valueObject->contentInfo->modificationDate;
            }
        }

        //Set the etag and modification date on the response
        $response = $this->buildResponse(
            __METHOD__ . $subTreeLocationId,
            $modificationDate
        );

        $response->headers->set( 'X-Location-Id', $subTreeLocationId );

        //If nothing has been modified, return a 304
        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        //Render the output
        return $this->render(
            'BlendPartialContentBundle::feature.html.twig',
            array(
                'posts' => $posts,
                'location' => $location,
                'post_results' => $postResults
            ),
            $response
        );
    }

    public function menu(
        $selected = null,
        $subTreeLocationId = false,
        $contentTypeIdentifiers = array()
    )
    {
        $homeLocationId = $this->getConfigResolver()->getParameter('root_location_id', 'partialcontent');
        if (!$subTreeLocationId) {
            $subTreeLocationId = $homeLocationId;
        }

        if (!count($contentTypeIdentifiers)) {
            $contentTypeIdentifiers = $this->getConfigResolver()->getParameter('menu_content_types', 'partialcontent');
        }

        //Retrieve the location service from the Symfony container
        $locationService = $this->getRepository()->getLocationService();

        //Load the called location (node) from the repository based on the ID
        $root = $locationService->loadLocation( $subTreeLocationId );

        //Set the etag and modification date on the response
        $response = $this->buildResponse(
            __METHOD__ . $subTreeLocationId . '-' . $selected,
            $root->contentInfo->modificationDate
        );

        //If nothing has been modified, return a 304
        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        //Retrieve a subtree fetch of the latest posts
        $results = $this->fetchSubTree(
            $root,
            $contentTypeIdentifiers,
            array(new SortClause\LocationPriority()),
            false
        );

        //Convert the results from a search result object into a simple array
        $locations = array();
        foreach ( $results->searchHits as $hit )
        {
            $locations[] = $locationService->loadLocation($hit->valueObject->contentInfo->mainLocationId);
        }

        $response->headers->set( 'X-Location-Id', $subTreeLocationId );

        //Render the output
        return $this->render(
            'BlendPartialContentBundle::top_menu.html.twig',
            array(
                'root' => $root,
                'locations' => $locations,
                'selectedLocationId' => $selected,
                'homeLocationId' => $homeLocationId
            ),
            $response
        );

    }


    /**
     * A convenience method to provide a simple method for retrieving selected objects.
     * Returns all content object from a subtree of content by type, based on the location
     * @param Location $subTreeLocation The location object representing a location (node) in the repository
     * @param array $typeIdentifiers an array of string containing identifiers for ContentTypes
     * @param array $sortMethods An array of sort methods
     * @param null $limit A number of objects to return
     * @param int $offset How many records to offset from teh start of the list
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     * @todo Factor this method out as a service to be used by other controllers
     */
    protected function fetchSubTree(
        \eZ\Publish\API\Repository\Values\Content\Location $subTreeLocation,
        array $typeIdentifiers=array(),
        array $sortMethods=array(),
        $searchTree = true,
        $limit = null,
        $offset = 0
    )
    {

        //Access the search service provided by the eZ Repository (Public API)
        $searchService = $this->getRepository()->getSearchService();

        $criterion = array(
            new Criterion\ContentTypeIdentifier( $typeIdentifiers )
        );

        if ($searchTree) {
            $criterion[] = new Criterion\Subtree( $subTreeLocation->pathString );
        } else {
            $criterion[] = new Criterion\ParentLocationId( $subTreeLocation->id );
        }

        //Construct a query
        $query = new Query();
        $query->criterion = new Criterion\LogicalAnd(
            $criterion
        );
        if ( !empty( $sortMethods ) )
        {
            $query->sortClauses = $sortMethods;
        }
        $query->limit = $limit;
        $query->offset = $offset;

        //Return the content from the repository
        return $searchService->findContent( $query );
    }

    /**
     * Build the RSS2 feed of the planet
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function feed($subTreeLocationId = 2)
    {
        $locationService = $this->getRepository()->getLocationService();
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $root = $locationService->loadLocation( $subTreeLocationId );

        $postResults = $this->fetchSubTree(
            $root,
            array('blog_post'),
            array( new SortClause\DatePublished( Query::SORT_DESC ) ),
            true,
            20
        );

        $modificationDate = $root->contentInfo->modificationDate;

        $posts = array();
        foreach ( $postResults->searchHits as $hit )
        {
            $posts[] = $hit->valueObject;

            //If any of the posts is newer than the root, use that post's modification date
            if ($hit->valueObject->contentInfo->modificationDate > $modificationDate) {
                $modificationDate = $hit->valueObject->contentInfo->modificationDate;
            }
        }

        $response = $this->buildResponse(
            __FUNCTION__ . $subTreeLocationId,
            $modificationDate
        );

        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        $feed = new ezcFeed();
        $feed->title = 'Partial Content';
        $feed->description = '';
        $feed->published = time();
        $feed->description = "A blog about code and stuff from Joe Kepley";
        $pc = $feed->add('author');
        $pc->name = "Partial Content";
        $pc->uri = "http://partialcontent.com";
        $feed->updated = $modificationDate;
        $feed->id = "http://partialcontent.com/feed/pc";
        $link = $feed->add( 'link' );
        $link->href = 'http://partialcontent.com';
        $link->type = "text/html";

        $selfLink = $feed->add('link');
        $selfLink->href = "http://partialcontent.com/feed/pc";
        $selfLink->rel = "self";
        $selfLink->type = "application/atom+xml";

        $converter = $this->container->get("ezpublish.fieldType.ezxmltext.converter.html5");

        foreach ( $posts as $post )
        {
            $location = $locationService->loadLocation(
                $post->contentInfo->mainLocationId
            );
            $item = $feed->add( 'item' );
            $item->title = htmlspecialchars(
                $post->contentInfo->name, ENT_NOQUOTES, 'UTF-8'
            );
            $guid = $item->add( 'id' );
            $guid->id = "http://partialcontent.com" . $urlAliasService->reverseLookup($location)->path;
            $guid->isPermaLink = "true";
            $item->link = "http://partialcontent.com" . $urlAliasService->reverseLookup($location)->path;
            $item->pubDate = $post->contentInfo->modificationDate; //$post->getField( 'date' )->value->value;
            $item->updated = $post->contentInfo->modificationDate;
            $item->published =  $post->contentInfo->modificationDate; // $post->getField( 'date' )->value->value;
            //echo "<pre>"; print_r($post->getFieldValue('body')); echo "</pre>";

            $html = $converter->convert($post->getFieldValue( 'body' )->xml);

            $item->description = $html;
            $item->description->type='html';

            $joe = $item->add('author');
            $joe->name = "Joe Kepley";
            $joe->uri = "http://partialcontent.com";
            $item->author = $joe;
            $dublinCore = $item->addModule( 'DublinCore' );
            $creator = $dublinCore->add( 'creator' );
            //$parentLocation =
            $creator->name = htmlspecialchars(
                $locationService->loadLocation(
                    $location->parentLocationId
                )->contentInfo->name,
                ENT_NOQUOTES, 'UTF-8'
            );

        }

        $xml = $feed->generate( 'atom' );
        $response->headers->set( 'content-type', $feed->getContentType() );
        $response->setContent( $xml );
        return $response;
    }

}
