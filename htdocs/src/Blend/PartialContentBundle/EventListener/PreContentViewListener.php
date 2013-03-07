<?php
/**
 * File containing the PreContentViewListener class.
 *
 */

namespace Blend\PartialContentBundle\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface,
    eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Relation,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion,
    eZ\Publish\API\Repository\Values\Content\Query\SortClause,
    eZ\Publish\API\Repository\Repository;


class PreContentViewListener
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;


    /**
     * Constructs our listener and loads it with access to the eZ Publish repository and config
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct( Repository $repository, ConfigResolverInterface $configResolver )
    {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Fires just before the page is rendered
     * @param \eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent $event
     */
    public function onPreContentView( PreContentViewEvent $event )
    {
        //What's our design/surround object in the repository called? Check the config
        $surroundTypeIdentifier = $this->configResolver->getParameter('surround_type', 'partialcontent');

        //To retrieve the surround object, first access the repository
        $searchService = $this->repository->getSearchService();

        //Find the first object that matched the name from our config
        $surround = $searchService->findSingle(
            new Criterion\ContentTypeIdentifier($surroundTypeIdentifier)
        );

        //Get the header image field from the surround
        $header_image = $surround->getField('header_image');

        //Retrieve the view context from the event
        $contentView = $event->getContentView();


        $params = array(
            'surround' => $surround,
            'header_image' => $header_image,
            'header_image_version' => $surround->versionInfo
        );

        if ($contentView->hasParameter('content')) {
            $contentTypeService = $this->repository->getContentTypeService();
            $contentService = $this->repository->getContentService();
            $content = $contentView->getParameter('content');
            $seriesType = $contentTypeService->loadContentTypeByIdentifier('series');

            //Retrieve all the relationships
            $relations = $contentService->loadReverseRelations($content->contentInfo);

            $series = array();

            //See if we have a related series
            foreach ($relations as $relation) {
                //Consider anything related by a field as a part of a series
                if (
                    $relation->type == Relation::FIELD &&
                    $relation->sourceContentInfo->contentTypeId == $seriesType->id
                ) {
                    $series[] = $relation->sourceContentInfo;
                }
            }

            if (count($series)) {
                $seriesHeaderObj = $contentService->loadContentByContentInfo($series[0]);
                if ($seriesHeaderObj->getField('header_image')->value) {
                    $params['header_image']=$seriesHeaderObj->getField('header_image');
                    $params['header_image_version']=$seriesHeaderObj->versionInfo;
                }
            }


            $params['series']=$series;
        }
        $contentView->addParameters( $params );
    }
}
