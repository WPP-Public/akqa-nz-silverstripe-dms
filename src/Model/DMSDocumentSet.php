<?php

namespace Sunnysideup\DMS\Model;

use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;

/**
 * A document set is attached to Pages, and contains many DMSDocuments
 *
 * @property Varchar Title
 * @property  Text KeyValuePairs
 * @property  Enum SortBy
 * @property Enum SortByDirection
 */
class DMSDocumentSet extends DataObject
{

    private static $table_name = 'DMSDocumentSet';

    private static $singular_name = 'DMS Document Set';

    private static $plural_name = 'DMS Document Sets';

    private static $db = [
        'Title' => 'Varchar(255)',
        'KeyValuePairs' => 'Text',
        'SortBy' => "Enum('LastEdited,Created,Title')')",
        'SortByDirection' => "Enum('DESC,ASC')')",
    ];

    private static $has_one = [
        'Page' => SiteTree::class,
    ];

    private static $many_many = [
        'Documents' => DMSDocument::class,
    ];

    private static $many_many_extraFields = [
        'Documents' => [
            // Flag indicating if a document was added directly to a set - in which case it is set - or added
            // via the query-builder.
            'ManuallyAdded' => 'Boolean(1)',
            'DocumentSort' => 'Int'
        ],
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Documents.Count' => 'No. Documents'
    ];

    /**
     * Retrieve a list of the documents in this set. An extension hook is provided before the result is returned.
     *
     * You can attach an extension to this event:
     *
     * <code>
     * public function updateDocuments($document)
     * {
     *     // do something
     * }
     * </code>
     *
     * @return DataList|null
     */
    public function getDocuments()
    {
        $documents = $this->Documents();
        $this->extend('updateDocuments', $documents);
        return $documents;
    }



    /**
     * Customise the display fields for the documents GridField
     *
     * @return array
     */
    public function getDocumentDisplayFields()
    {
        return array_merge(
            (array) DMSDocument::create()->config()->get('display_fields'),
            ['ManuallyAdded' => _t('DMSDocumentSet.ADDEDMETHOD', 'Added')]
        );
    }

    public function validate()
    {
        $result = parent::validate();

        if (!$this->getTitle()) {
            $result->addError(_t('DMSDocumentSet.VALIDATION_NO_TITLE', '\'Title\' is required.'));
        }
        return $result;
    }

    public function canView($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->getGlobalPermission($member);
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->getGlobalPermission($member);
    }

    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->getGlobalPermission($member);
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->getGlobalPermission($member);
    }

    /**
     * Checks if a then given (or logged in) member is either an ADMIN, SITETREE_EDIT_ALL or has access
     * to the DMSDocumentAdmin module, in which case permissions is granted.
     *
     * @param Member $member
     * @return bool
     */
    public function getGlobalPermission(Member $member = null)
    {
        if (!$member || !(is_a($member, Member::class)) || is_numeric($member)) {
            $member = Member::currentUser();
        }

        $result = (
            $member &&
            Permission::checkMember(
                $member,
                ['ADMIN', 'SITETREE_EDIT_ALL', 'CMS_ACCESS_DMSDocumentAdmin']
            )
        );

        return (bool) $result;
    }
}
