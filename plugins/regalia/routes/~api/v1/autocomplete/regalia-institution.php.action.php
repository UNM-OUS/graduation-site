<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS_Plugins\unmous\ous_regalia\Regalia;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(400);
}

Context::response()->filename('response.json');

$institutions = [];
// exact phrase matches
$query = Regalia::institutions();
if ($phrase = trim(Context::arg('query'))) {
    $query->where('regalia_institution.label like ?', "%$phrase%");
}
$query->limit(20);
$institutions = $institutions + $query->fetchAll();
// fuzzier matches
$query = Regalia::institutions();
foreach (explode(' ', Context::arg('query')) as $word) {
    $word = strtolower(trim($word));
    if ($word) {
        $query->where('regalia_institution.label like ?', "%$word%");
    }
}
$query->limit(10);
$institutions = $institutions + $query->fetchAll();

echo json_encode(
    array_map(
        function (array $institution) {
            return [
                'html' => sprintf('<div class="label">%s</div>', $institution['label']),
                'value' => $institution['id']
            ];
        },
        $institutions
    )
);
