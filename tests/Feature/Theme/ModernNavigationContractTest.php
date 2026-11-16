<?php

/**
 * Regression: BC-01 — navigasi halaman modern wajib memakai URL kanonik.
 *
 * Index penelitian pernah hardcode `/research/proposal/{id}/modern` sementara
 * pengabdian memakai `show_url`. Akibatnya URL detail tidak konsisten dan
 * tema "loncat" ke klasik. Test ini membaca sumber JSX sebagai kontrak statis.
 */
function modernSource(string $relativePath): string
{
    return (string) file_get_contents(resource_path('js/modern/'.$relativePath));
}

it('uses canonical show_url for research proposal detail', function () {
    $source = modernSource('ProposalsIndex.jsx');

    expect($source)
        ->toContain('href={p.show_url}')
        ->not->toContain('/research/proposal/${p.id}/modern');
});

it('uses canonical show_url for community-service proposal detail', function () {
    $source = modernSource('PengabdianIndex.jsx');

    expect($source)
        ->toContain('href={p.show_url}')
        ->not->toContain('/community-service/proposal/${p.id}/modern');
});

it('module subpage fallback uses canonical show_url', function () {
    $source = modernSource('components/ModuleSubpageIndex.jsx');

    expect($source)
        ->toContain('actionUrlFn(p) : p.show_url')
        ->not->toContain('/proposal/${p.id}/modern');
});
