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

    protected $configResolver;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;



    public function __construct( Repository $repository, ConfigResolverInterface $configResolver )
    {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    public function onPreContentView( PreContentViewEvent $event )
    {
        $surroundTypeIdentifier = $this->configResolver->getParameter('surround_type', 'partialcontent');

        //Retrieve the surround object
        $searchService = $this->repository->getSearchService();
        $surround = $searchService->findSingle( new Criterion\ContentTypeIdentifier($surroundTypeIdentifier) );
        $header_image = $surround->getField('header_image');
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
