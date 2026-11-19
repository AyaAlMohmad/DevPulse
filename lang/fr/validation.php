<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'url' => 'Le champ :attribute doit être une URL HTTP ou HTTPS valide.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
    ],
    'attributes' => [
        'name' => 'nom', 'email' => 'adresse e-mail', 'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe', 'remember' => 'mémorisation',
        'url' => 'URL du service', 'interval_seconds' => 'intervalle de contrôle', 'search' => 'recherche',
        'status' => 'statut', 'locale' => 'langue', 'redirect' => 'page de retour',
    ],
];
