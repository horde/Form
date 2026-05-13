<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * SelectfilesVariable type for file selection fields.
 *
 * @property string $selectid Contains gollem selectfile selectionID
 * @property string $link_text The text to use in the link
 * @property string $link_style The style to use for the link
 * @property bool $icon Create the link with an icon instead of text

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_selectfiles PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class SelectfilesVariable extends BaseVariable
{
    /**
     * The text to use in the link.
     *
     * @var string
     */
    public $_link_text;

    /**
     * The style to use for the link.
     *
     * @var string
     */
    public $_link_style;

    /**
     *  Create the link with an icon instead of text?
     *
     * @var boolean
     */
    public $_icon;

    /**
     * Contains gollem selectfile selectionID
     *
     * @var string
     */
    public $_selectid;

    /**
     * Initialize a file selection field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string $selectid - Contains gollem selectfile selectionID
     *                      - $params[1]: string|null $link_text - The text to use in the link (default: "Select Files")
     *                      - $params[2]: string $link_style - The style to use for the link (default: '')
     *                      - $params[3]: bool $icon - Create the link with an icon instead of text (default: false)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_selectid = $params[0];
        $link_text = $params[1] ?? null;
        $link_style = $params[2] ?? '';
        $icon = $params[3] ?? false;

        if (is_null($link_text)) {
            $link_text = Horde_Form_Translation::t("Select Files");
        }

        $this->_link_text = $link_text;
        $this->_link_style = $link_style;
        $this->_icon = $icon;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $value = $this->getValue($vars);
        return $GLOBALS['registry']->call('files/selectlistResults', [$value]);
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("File selection"),
            'params' => [
                'selectid' => [
                    'label' => Horde_Form_Translation::t("Id"),
                    'type' => 'text',
                ],
                'link_text' => [
                    'label' => Horde_Form_Translation::t("Link text"),
                    'type' => 'text',
                ],
                'link_style' => [
                    'label' => Horde_Form_Translation::t("Link style"),
                    'type' => 'text',
                ],
                'icon' => [
                    'label' => Horde_Form_Translation::t("Show icon?"),
                    'type' => 'boolean',
                ],
            ],
        ];
    }
}
