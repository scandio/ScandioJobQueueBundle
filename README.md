ScandioJobQueueBundle
=====================

A simple pagination bundle for Symfony2 without the fuzz.

## Requirements:

- Symfony2 **>=2.1.0**
- Twig **>=1.5** version

## Installation

Install via composer.json:

    ...
    "repositories": [
            {
                "type": "git",
                "url": "https://github.com/scandio/ScandioPaginatorBundle.git"
            }
        ],
    ...
     "require": {
            ...
            "scandio/paginator-bundle": "dev-master"
            ...
        },


If you use a `deps` file, add:

    [ScandioPaginatorBundle]
        git=http://github.com/scandio/ScandioPaginatorBundle.git
        target=bundles/Scandio/PaginatorBundle

Or if you want to clone the repos:

    # Install Paginator
    git clone git://github.com/scandio/ScandioPaginatorBundle.git vendor/bundles/Scandio/PaginatorBundle

## Usage

You have to build your own database function! This is **NOT** a doctrine query paginator! Use [KnpPaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle) for this.

``` php
<?php
$em = $this->get('doctrine')->getEntityManager();
$paginator = $this->get('scandio.paginator');
$repository = $em->getRepository('...');
$limit = 10;

$attributes = $repository->getAll($page, $limit);
$maxCount = $repository->getAllCount();

// fill with data
$paginator->setLimit($limit);
$paginator->setPage($page);
$paginator->setList($attributes);
$paginator->setTotalCount($maxCount);

return array(
	'page' => $page,
	'attributes' => $paginator
);
```

### View

``` html
<ul>
{% for attribute in attributes %}
    <li>{{attribute}}</li>
{% endfor %}
</ul>

{# use pagination link here. Use "page" for pagination index number #}
{{ attributes.paginationBar('attributes_pagination') }}
```