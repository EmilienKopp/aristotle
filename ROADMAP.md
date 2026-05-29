# Aristotle Roadmap

DDD scaffolding and base classes for Laravel. Each milestone adds one layer of the domain stack.

---

## v0.1 — Entity Layer ✅
- `BaseEntity` — constructor property promotion, `fromArray`, `ArrayLike` trait
- `make:entity` — scaffold from Eloquent model schema
  - Domain prompt with folder discovery
  - Model prompt with autocomplete from `app/Models/`
  - Configurable namespace root, entities sub-folder, class suffix
  - `--database` flag for multitenancy

---

## v0.2 — DTO Layer
- `BaseDTO` — immutable, `fromArray`, `fromValidatable` (auto-coerces `*_id` fields), `toArray`
- `DTO` interface
- `make:dto` — domain-aware, same prompt conventions as `make:entity`
  - `--model` flag to scaffold properties from model fillable/casts
  - Optional `--for=create|update` to generate purpose-scoped DTOs

---

## v0.3 — Repository Layer
- `RepositoryInterface` — base contract: `find`, `findById`, `all`, `save`, `delete`
- `make:repository` — generates the interface + Eloquent implementation pair
  - Domain prompt places interface in `App\Domain\{Domain}\Contracts\`
  - Implementation in `App\Infrastructure\Persistence\{Domain}\`
  - Wires entity type hints from detected entity class

---

## v0.4 — Application Layer
- `make:action` — domain-aware, generates Create / Update / Delete action stubs
  - `--type=create|update|delete` (prompted if omitted)
  - Placed in `App\Application\{Domain}\Actions\`
  - Injects repository interface via constructor
- `make:query` — generates Index / Show query stubs
  - `--type=index|show` (prompted if omitted)
  - Placed in `App\Application\{Domain}\Queries\`

---

## v0.5 — Eloquent Bridge
- `HasEntity` trait for Eloquent models
  - `toEntity(): EntityClass` — maps model attributes to entity constructor
  - `fromEntity(Entity): static` — fills model from entity for save
  - `toEntityCollection(): Collection<EntityClass>` — maps Eloquent collections
- `make:entity` gains `--with-bridge` flag to patch the model automatically

---

## v0.6 — Value Object Layer
- `make:vo` — thin wrapper around `harris21/laravel-aegis`
  - Soft dependency: command only registers when aegis is installed
  - Adds domain prompt → places VO in `App\Domain\{Domain}\ValueObjects\`
  - Passes `--namespace`, `--rule`, `--cast`, `--normalize` through to aegis
- `CastTypeResolver` detects columns whose cast maps to a known VO class and emits that type in `make:entity` output

---

## v1.0 — Domain Scaffold
- `make:domain` — one command to scaffold a full bounded context interactively
  - Prompts: domain name, model(s), which artifacts to generate
  - Chains: entity → DTO → repository interface + impl → actions → queries
  - Summary output showing all created files
