<?php

namespace Sunnysideup\DMS\Cms;

// use UploadField_ItemHandler;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Forms\Form;

class DMSUploadField_ItemHandler
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var array<string>
     * @config
     */
    private static array $allowed_actions = [
        'delete',
        'edit',
        'EditForm',
    ];

    /**
     * @var int
     */
    protected int $itemID;

    /**
     * @var DMSUploadField
     */
    protected DMSUploadField $parent;

    /**
     * Constructor
     *
     * @param DMSUploadField $parent
     * @param int $itemID
     */
    public function __construct(DMSUploadField $parent, int $itemID)
    {
        $this->parent = $parent;
        $this->itemID = $itemID;
    }

    /**
     * Create a new instance of the handler
     *
     * @param DMSUploadField $parent
     * @param int $itemID
     * @return static
     */
    public static function create(DMSUploadField $parent, int $itemID): self
    {
        return new static($parent, $itemID);
    }

    /**
     * Gets a DMS document by its ID
     *
     * @return DMSDocument|null
     */
    public function getItem(): ?DMSDocument
    {
        return DMSDocument::get()->byId($this->itemID);
    }

    /**
     * Get the edit form for the document
     *
     * @return Form
     * @throws \RuntimeException If no document is found
     */
    public function EditForm(): Form
    {
        $file = $this->getItem();

        if (!$file) {
            throw new \RuntimeException('Document not found');
        }

        // Get form components
        $fields = $this->parent->getDMSFileEditFields($file);
        $actions = $this->parent->getDMSFileEditActions($file);
        $validator = $this->parent->getDMSFileEditValidator($file);

        $form = Form::create(
            $this,
            __FUNCTION__,
            $fields,
            $actions,
            $validator
        );

        $form->loadDataFrom($file);

        return $form;
    }
}