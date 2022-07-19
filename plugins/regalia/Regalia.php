<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use Envms\FluentPDO\Queries\Select;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use Envms\FluentPDO\Query;

class Regalia extends AbstractPlugin
{
    public static function query(): Query
    {
        return SharedDB::query();
    }

    public static function getPersonInfo(string $for)
    {
        return static::people()
            ->where('identifier = ?', [$for])
            ->fetch();
    }

    public static function people(): Select
    {
        return SharedDB::query()
            ->from('regalia_person');
    }

    public static function institution(int $id)
    {
        return static::allInstitutions()
            ->where('regalia_institution.id = ?', [$id])
            ->fetch();
    }

    public static function preset(int $id)
    {
        return static::allPresets()
            ->where('regalia_preset.id = ?', [$id])
            ->fetch();
    }

    public static function field(int $id)
    {
        $field = static::fields()
            ->where('regalia_field.id = ?', [$id])
            ->fetch();
        if (!$field) return null;
        return $field;
    }

    public static function allFields(): Select
    {
        return SharedDB::query()
            ->from('regalia_field')
            ->leftJoin('jostens_field ON regalia_field.jostens_id = jostens_field.id')
            ->select('jostens_field.*')
            ->select('regalia_field.id as id');
    }

    public static function fields(): Select
    {
        return static::allFields()
            ->where('(field_deprecated <> 1 AND deprecated <> 1)');
    }

    public static function allInstitutions(): Select
    {
        return SharedDB::query()
            ->from('regalia_institution')
            ->leftJoin('jostens_institution ON regalia_institution.jostens_id = jostens_institution.id')
            ->select('regalia_institution.id as id')
            ->select('jostens_institution.institution_name as jostens_name')
            ->select('jostens_institution.institution_city as jostens_city')
            ->select('jostens_institution.institution_state as jostens_state')
            ->select('jostens_institution.institution_color_lining1 as color_lining')
            ->select('jostens_institution.institution_color_chevron1 as color_chevron');
    }

    public static function allPresets(): Select
    {
        return SharedDB::query()
            ->from('regalia_preset')
            ->leftJoin('regalia_field on regalia_preset.field = regalia_field.id')
            ->leftJoin('jostens_field on regalia_field.jostens_id = jostens_field.id')
            ->select('regalia_preset.id as id')
            ->select('regalia_preset.label as label')
            ->select('regalia_field.id as field_id')
            ->select('regalia_field.label as field_label')
            ->select('regalia_field.deprecated as field_deprecated')
            ->select('jostens_field.field_name as jostens_name')
            ->select('jostens_field.id as jostens_id')
            ->select('jostens_field.field_deprecated as jostens_deprecated')
            ->order('weight ASC, regalia_preset.label ASC');
    }

    public static function presets(): Select
    {
        return static::allPresets()
            ->where('regalia_preset.deprecated <> 1')
            ->where('(regalia_field.deprecated IS NULL OR regalia_field.deprecated <> 1)')
            ->where('(jostens_field.field_deprecated IS NULL OR jostens_field.field_deprecated <> 1)');
    }

    public static function institutions(): Select
    {
        return static::allInstitutions()
            ->where('(institution_deprecated <> 1 AND deprecated <> 1)');
    }

    public static function onUserMenu_user(UserMenu $menu)
    {
        $menu->addURL(new URL('/~regalia/'));
    }

    public static function onStaticUrlPermissions_regalia(URL $url)
    {
        if ($url->route() == 'regalia/global_settings') return Permissions::inMetaGroup('regalia__admin');
        else return Permissions::inMetaGroup('regalia__edit');
    }
}
