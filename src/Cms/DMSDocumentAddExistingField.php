<?php

namespace Sunnysideup\DMS\Cms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\CompositeField;

class DMSDocumentAddExistingField extends CompositeField
{
    public $useFieldContext = true;

    /**
    * @var DataObject
    */
   protected $record;


    public function __construct($name, $title = null)
    {
        $this->name = $name;
        $this->title = ($title === null) ? $name : $title;

        parent::__construct(
            new TreeDropdownField(
                'PageSelector',
                'Add from another page',
                SiteTree::class,
                'ID',
                'TitleWithNumberOfDocuments'
            )
        );
    }

    /**
     * Force a record to be used as "Parent" for uploaded Files (eg a Page with a has_one to File)
     * @param DataObject $record
     */
    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }

    /**
     * Get the record to use as "Parent" for uploaded Files (eg a Page with a has_one to File) If none is set, it
     * will use Form->getRecord() or Form->Controller()->data()
     * @return DataObject
     */
    public function getRecord()
    {
        if (!$this->record && $this->form) {
            if ($this->form->getRecord() && is_a($this->form->getRecord(), DataObject::class)) {
                $this->record = $this->form->getRecord();
            } elseif (
                $this->form->Controller() && $this->form->Controller()->hasMethod('data')
                && $this->form->Controller()->data() && is_a($this->form->Controller()->data(), DataObject::class)
            ) {
                $this->record = $this->form->Controller()->data();
            }
        }
        return $this->record;
    }

    public function FieldHolder($properties = [])
    {
        return $this->Field($properties);
    }

    public function Field($properties = [])
    {
        Requirements::javascript('heyday/silverstripe-dms:javascript/DMSDocumentAddExistingField.js');
        Requirements::javascript('heyday/silverstripe-dms:javascript/DocumentHTMLEditorFieldToolbar.js');
        Requirements::css('heyday/silverstripe-dms:dist/css/cmsbundle.css');

        return $this->renderWith(self::class);
    }

    /**
     * Sets or unsets the use of the "field" class in the template. The "field" class adds Javascript behaviour
     * that causes unwelcome hiding side-effects when this Field is used within the link editor pop-up
     *
     * @return $this
     */
    public function setUseFieldClass($use = false)
    {
        $this->useFieldContext = $use;
        return $this;
    }
}
