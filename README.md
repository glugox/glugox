# Glugox Monorepo Overview

This repository hosts the Glugox automation toolchain. It is organised as a monorepo so that the
framework core, the build-time tooling, and the runtime module loader evolve together. The end goal
is to turn high-level JSON entity definitions into fully working Laravel modules that can be plugged
into a sandbox application for rapid iteration and testing.

## Package responsibilities

The repository is split into three coordinated packages:

- **`glugox/core`** – The runtime foundation shared by every generated module. It exposes the base
  Laravel service providers, routing helpers, scaffolding for controllers/models, and shared
  utilities that modules rely on.
- **`glugox/builder`** – The code generator that reads JSON entity definitions and writes PHP and
  Vue scaffolding. It owns the parsing pipeline, validation rules, templating system, and file
  writers used during module creation.
- **`glugox/module`** – The reusable runtime bundle produced by the builder. Each generated module is
  packaged here along with its module manifest, migrations, and UI assets. The package also
  orchestrates module registration inside the sandbox Laravel app.

## Entity definition flow

1. Authors describe domains with JSON entity files (see [Configuration format](#configuration-format)).
2. `glugox/builder` validates the JSON payloads, resolves relationships, and emits typed in-memory
   entity graphs.
3. The builder transforms the graphs into filesystem artefacts (models, controllers, migrations,
   Vue components, tests, etc.).
4. Generated artefacts are wrapped in a module bundle under `glugox/module`.
5. The sandbox Laravel app imports the module bundle through `glugox/core` service providers and
   boots routes, views, and database structures automatically.

## Quick start

### Bootstrap the sandbox Laravel app

1. Clone the repository and install Composer dependencies:
   ```bash
   git clone https://github.com/<org>/glugox.git
   cd glugox
   composer install
   ```
2. Prepare the environment file and generate the Laravel key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Install front-end dependencies and build assets if the sandbox uses Vite:
   ```bash
   npm install
   npm run build
   ```
4. Migrate the database and seed demo data:
   ```bash
   php artisan migrate --seed
   ```
5. Start the sandbox server:
   ```bash
   php artisan serve
   ```

### Generate, install, and orchestrate modules

1. Create or update JSON entity definitions in the `/entities` directory.
2. Run the builder to generate module code:
   ```bash
   php artisan glugox:build entities/*.json
   ```
3. Publish the generated module into the sandbox Laravel app:
   ```bash
   php artisan glugox:install <module-name>
   ```
4. Enable the module by adding it to the module manifest (usually `config/glugox.php`).
5. Rebuild front-end assets and clear caches if required:
   ```bash
   npm run build
   php artisan config:clear
   php artisan route:clear
   ```
6. Verify the module routes and UI under the running sandbox server.

## Configuration format

Entity definitions are written as JSON files. Each file describes a single domain entity. A minimal
file looks like this:

```json
{
  "name": "Post",
  "table": "posts",
  "fields": [
    {
      "name": "title",
      "type": "string",
      "length": 255,
      "required": true,
      "unique": true,
      "validation": ["max:255"]
    },
    {
      "name": "published_at",
      "type": "datetime",
      "nullable": true
    }
  ],
  "relationships": [
    {
      "type": "belongsTo",
      "entity": "User",
      "foreignKey": "user_id"
    }
  ],
  "ui": {
    "list": ["title", "published_at"],
    "form": ["title", "published_at"]
  }
}
```

### Required keys

- `name` – PascalCase name of the entity and generated model class.
- `table` – Database table used for migrations.
- `fields` – Array describing each column/attribute. Field objects typically include:
  - `name` – Snake_case column name.
  - `type` – Laravel migration column type (`string`, `integer`, `boolean`, `datetime`, etc.).
  - Optional metadata such as `length`, `default`, `required`, `nullable`, `unique`, and custom
    `validation` rules.
- `relationships` – Optional array that maps relationships (e.g. `belongsTo`, `hasMany`,
  `morphMany`). Each relationship declares the `entity` target and key configuration.
- `ui` – Optional hints used by the builder to scaffold Vue components (list columns, form layout,
  filters, etc.).

### Advanced options

- `policies` – Attach authorisation policies to generated controllers.
- `hooks` – Register lifecycle hooks for model events (`creating`, `updating`, etc.).
- `seeds` – Provide seed data consumed when running `php artisan db:seed`.

These conventions keep JSON definitions expressive while remaining declarative. Contributors should
update this README whenever new keys or behaviours are introduced to the pipeline.

