<?php

use SimonHamp\Gitamic\Repository;
use SimonHamp\Gitamic\Contracts\SiteRepository;

test('branch names', function ($branch, $expected, $dump = false) {
    /** @var Repository $repo */
    $repo = app(SiteRepository::class);

    $meta = $repo->parseBranchMeta($branch);

    expect($meta)->toMatchArray($expected);
})->with([
    [
        '* master   99cb46a [origin/master] Switch to Statamic (#1)  ',
        [
            "current" => true,
            "name" => "master",
            "tracking" => "origin/master",
            "commit" => "99cb46a"
        ]
    ],
    [
        '* master   99cb46a [origin/master] Switch to Statamic (#1)  ',
        [
            "current" => true,
            "name" => "master",
            "tracking" => "origin/master",
            "commit" => "99cb46a"
        ]
    ],
    [
        '* master   99cb46a [origin/master] Switch to Statamic (#1) ',
        [
            "current" => true,
            "name" => "master",
            "tracking" => "origin/master",
            "commit" => "99cb46a"
        ]
    ],
    [
        '* master   99cb46a [origin/master] Switch to Statamic (#1)',
        [
            "current" => true,
            "name" => "master",
            "tracking" => "origin/master",
            "commit" => "99cb46a"
        ]
    ],
    [
        '  statamic e3ba2c0 [origin/statamic: gone]  Update dependencies  ',
        [
            "current" => false,
            "name" => "statamic",
            "tracking" => "origin/statamic",
            "commit" => "e3ba2c0"
        ],
        true
    ],
    [
        '* main 22924b90 [origin/main] update gitmatic',
        [
            "current" => true,
            "name" => "main",
            "tracking" => "origin/main",
            "commit" => "22924b90"
        ]
    ],
    [
        'loc 6f1ace6 wip',
        [
            "current" => false,
            "name" => "loc",
            "tracking" => "not tracking",
            "commit" => "6f1ace6"
        ]
    ],
    [
        'loc 6f1ace6 [origin/loc] wip',
        [
            "current" => false,
            "name" => "loc",
            "tracking" => "origin/loc",
            "commit" => "6f1ace6"
        ]
    ],
    [
        'loc 6f1ace6 wip test',
        [
            "current" => false,
            "name" => "loc",
            "tracking" => "not tracking",
            "commit" => "6f1ace6"
        ]
    ],
]);
