<?php
/**
 * Copyright 2001-2025 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 */

namespace Horde\Form\V3;
use Horde_Variables;

interface Variable
{
    public function getMessage();
    public function setFormOb($form);
    public function setDefault($value);
    public function getDefault();
    public function setAction($action);
    public function hasAction();
    public function hide();
    public function isHidden();
    public function disable();
    public function isDisabled();
    public function getHumanName();
    public function getTypeName(): string;
    public function getVarName();
//    public function getType();
//    public function isRequired();
//    public function isReadonly();
    public function hasDescription(): bool;
    public function getDescription();
    public function isArrayVal();
    public function isUpload();
    public function setHelp($help);
    public function hasHelp();
    public function getHelp();
    public function setOption($option, $val);
    public function getOption($option);

    public function getInfo($vars, ...$args);

    public function wasChanged($vars);
    public function validate($vars, $message);
    public function getValue($vars, $index = null);
    public function invalid(string $message): bool;

    // Former type methods
    public function getProperty($property);
    public function setProperty($property, $value);
    public function init(...$params);
    public function onSubmit($vars);
    //public function isValid(Horde_Variables|array $vars, $value): bool;
    public function getValues(...$params);
    public function about(): array;
}
