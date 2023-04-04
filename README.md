# lsif-php [![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/davidrjenni/lsif-php)

[![CI](https://github.com/davidrjenni/lsif-php/actions/workflows/ci.yml/badge.svg)](https://github.com/davidrjenni/lsif-php/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/davidrjenni/lsif-php/branch/main/graph/badge.svg?token=4NZWCF6LZS)](https://codecov.io/gh/davidrjenni/lsif-php)
[![License: MIT](https://img.shields.io/github/license/davidrjenni/lsif-php)](https://github.com/davidrjenni/lsif-php/blob/main/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/davidrjenni/lsif-php)](https://packagist.org/packages/davidrjenni/lsif-php)
[![PHP Version](https://img.shields.io/packagist/php-v/davidrjenni/lsif-php)](https://packagist.org/packages/davidrjenni/lsif-php)
[![Docker Image Version](https://img.shields.io/docker/v/davidrjenni/lsif-php?label=docker)](https://hub.docker.com/r/davidrjenni/lsif-php)
[![Docker Image Size](https://img.shields.io/docker/image-size/davidrjenni/lsif-php)](https://hub.docker.com/r/davidrjenni/lsif-php)

Language Server Indexing Format (LSIF) generator for PHP

---

This repository is indexed using itself and available on [Sourcegraph](https://sourcegraph.com/github.com/davidrjenni/lsif-php).

## Requirements

`lsif-php` needs the `composer.json` and `composer.lock` file of
the project to index present in the current directory. It uses the
[`autoload`](https://getcomposer.org/doc/04-schema.md#autoload) and
[`autoload-dev`](https://getcomposer.org/doc/04-schema.md#autoload-dev)
properties to determine which directories to scan.

## Usage

To use a self-hosted Sourcegraph instance, set the
`SRC_ENDPOINT` and `SRC_ACCESS_TOKEN` [environment
variables](https://docs.sourcegraph.com/cli/explanations/env).

### GitHub Actions

Add the following job to your workflow:

```yml
on:
  - push

jobs:
  lsif-php:
    runs-on: ubuntu-latest
    container: davidrjenni/lsif-php:main
    steps:
      - uses: actions/checkout@v3
      - name: Generate LSIF data
        run: lsif-php
      - name: Apply container owner mismatch workaround
        run: |
          # FIXME: see https://github.com/actions/checkout/issues/760
          git config --global --add safe.directory ${GITHUB_WORKSPACE}
      - name: Upload LSIF data
        run: src code-intel upload -github-token=${{ secrets.GITHUB_TOKEN }}
```

### [GitLab CI/CD](https://docs.gitlab.com/ee/user/project/code_intelligence.html)

Add the following job to your pipeline:

```yml
code_navigation:
  image: davidrjenni/lsif-php:main
  artifacts:
    reports:
      lsif: dump.lsif
  script:
    - lsif-php
    - src code-intel upload
```

### Manual

Install [`lsif-php`](https://packagist.org/packages/davidrjenni/lsif-php)
with `composer` and the
[`src`](https://docs.sourcegraph.com/cli/quickstart) binary. Then generate
the LSIF data and upload it:

```bash
$ composer require --dev davidrjenni/lsif-php
$ vendor/bin/lsif-php
$ src code-intel upload
```
