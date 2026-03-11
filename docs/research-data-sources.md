# iLicitaciones - Investigacion de Fuentes de Datos
> Fecha: 2026-03-11 | Investigacion automatizada con 6 agentes en paralelo

---

## 1. PRESUPUESTOS

### 1.1 PGE Nacional

| Fuente | Formato | Cobertura | Estado | Automatizable |
|--------|---------|-----------|--------|---------------|
| **IGAE** (igae.pap.hacienda.gob.es) | XLSX mensual | 2003-2025 ejecucion | Funciona | SI - URLs predecibles por año/mes |
| **SEPG Estadisticas** | XLS anual | 1996-2025 PGE aprobados | Funciona | SI - descarga directa |
| datos.gob.es API REST | JSON | - | API ROTA (items vacios) | NO |
| Portal Hacienda Open Data | - | - | 404 | NO |
| data.europa.eu | - | - | 400 en queries | NO |

**Estrategia**: Descargar XLSX de IGAE (ejecucion) y SEPG (aprobados). Parsear con PhpSpreadsheet.

### 1.2 Presupuestos Municipales

| Fuente | Formato | Cobertura | Estado | Automatizable |
|--------|---------|-----------|--------|---------------|
| **Gobierto S3** | JSON (bubbles.json) | 8.119 municipios, 2010-2025 | FUNCIONA | SI - sin auth |
| **Gobierto places.json** | JSON | Indice 8.119 municipios con INE | FUNCIONA | SI |
| **Gobierto poblacion API** | JSON | Poblacion por municipio/año | FUNCIONA | SI |
| Madrid Open Data (datos.madrid.es) | CSV directo | 2011-2024 ejecucion | Funciona | SI |
| Gobierto API antigua | JSON | - | 404 en todos los endpoints | NO |
| SGCIEF Hacienda | Excel | CCAA (no municipal) | Sesion ASP.NET | Dificil |

**URLs clave Gobierto S3:**
```
Indice:     https://presupuestos.gobierto.es/places.json
Datos:      https://gobierto-populate-production.s3.eu-west-1.amazonaws.com/gobierto_budgets/{ine_code}/data/bubbles.json
Poblacion:  https://presupuestos.gobierto.es/api/data/widget/population/{ine_code}/{year}.json?places_collection=ine
```

**Estructura bubbles.json por entrada:**
- `budget_category`: "expense" | "income"
- `area_name`: "functional" | "economic"
- `level_2_es` / `level_2_ca` / `level_2_eu`: etiquetas en 3 idiomas
- `values`: objeto por año (2010-2025) con importes absolutos
- `values_per_inhabitant`: importes per capita por año
- `pct_diff`: variacion interanual %

**Nota**: INE code sin ceros a la izquierda (Madrid=28079, Barcelona=8019 no 08019)

### 1.3 Presupuestos Autonomicos

| Fuente | Formato | Cobertura | Estado |
|--------|---------|-----------|--------|
| SGCIEF Hacienda | Excel | Todas CCAA hasta 2026 | Sesion ASP.NET requerida |

---

## 2. LEGISLACION

### 2.1 BOE Open Data API (FUENTE PRINCIPAL - EXCELENTE)

**Base URL**: `https://www.boe.es/datosabiertos/api/`
**Auth**: Ninguna. Requiere header `Accept: application/json` o `application/xml`.
**Rate limits**: No documentados.

#### Endpoints disponibles

| Endpoint | Descripcion | Formato |
|----------|-------------|---------|
| `/legislacion-consolidada?from=YYYYMMDD&to=YYYYMMDD&offset=0&limit=50` | Buscar legislacion | JSON/XML |
| `/legislacion-consolidada/id/{id}` | Norma por ID | JSON/XML |
| `/legislacion-consolidada/id/{id}/metadatos` | Metadatos | JSON/XML |
| `/legislacion-consolidada/id/{id}/analisis` | Referencias cruzadas (DEROGA, MODIFICA...) | JSON/XML |
| `/legislacion-consolidada/id/{id}/texto` | Texto completo | Solo XML |
| `/legislacion-consolidada/id/{id}/texto/indice` | Indice de articulos | JSON/XML |
| `/legislacion-consolidada/id/{id}/texto/bloque/{block_id}` | Articulo individual | JSON/XML |
| `/boe/sumario/{YYYYMMDD}` | Sumario diario BOE | XML |
| `/borme/sumario/{YYYYMMDD}` | Sumario diario BORME | XML |
| `/datos-auxiliares/materias` | Categorias tematicas | JSON/XML |
| `/datos-auxiliares/departamentos` | Departamentos | JSON/XML |
| `/datos-auxiliares/rangos` | Rangos legales (Ley, RD, Orden...) | JSON/XML |
| `/datos-auxiliares/ambitos` | Ambitos (Estatal, Autonomico) | JSON/XML |

#### Campos por norma
- `identificador` (ej: BOE-A-2022-15818)
- `titulo`, `fecha_disposicion`, `fecha_publicacion`, `fecha_vigencia`
- `departamento` (con codigo)
- `rango` (Ley, Real Decreto, Ley Organica, Real Decreto-ley, Orden, Resolucion, etc.)
- `ambito` (Estatal/Autonomico)
- `numero_oficial` (ej: "18/2022")
- `estado_consolidacion`, `estatus_derogacion`, `vigencia_agotada`
- `url_eli` (identificador permanente europeo)

#### Volumen estimado
- 25.000-30.000+ normas consolidadas
- `limit=-1` devuelve max 10.000, requiere paginacion con offset

#### Analisis/Referencias cruzadas
El endpoint `/analisis` devuelve relaciones entre normas: MODIFICA, DEROGA, ANADE, con referencias a nivel de articulo y IDs de normas enlazadas.

**Documentacion PDF**: `https://www.boe.es/datosabiertos/documentos/APIconsolidada.pdf`

### 2.2 ELI (European Legislation Identifier)

- URLs permanentes: `https://www.boe.es/eli/es/{tipo}/{año}/{mes}/{dia}/{numero}`
- Incluido en respuestas del BOE API (campo `url_eli`)
- Mejor descubrir via API que construir manualmente

### 2.3 EUR-Lex (Legislacion UE)

| Fuente | Estado | Auth |
|--------|--------|------|
| SPARQL Endpoint (`publications.europa.eu/webapi/rdf/sparql`) | FUNCIONA | Sin auth |
| SOAP Web Service (`eur-lex.europa.eu/EURLexWebService`) | FUNCIONA | Requiere registro |

**SPARQL**: 2.4 billones+ triples RDF. Puede consultar directivas/reglamentos transpuestos a ley española.

### 2.4 Congreso / Senado

| Fuente | Estado | Datos |
|--------|--------|-------|
| Congreso datos abiertos | 403 Forbidden (bloqueado) | Diputados, biografias, comisiones |
| Senado XML | FUNCIONA | Todos los senadores desde 1977, partido, provincia |

### 2.5 Legislacion Autonomica

- BOE API cubre legislacion autonomica con `ambito: Autonomico`
- Boletines autonomicos individuales (DOGC, BOJA, BOCM): no investigados en profundidad

### 2.6 Estrategia recomendada

1. **Ingestion diaria**: `/boe/sumario/YYYYMMDD` para nuevas publicaciones
2. **Corpus completo**: Paginar `/legislacion-consolidada` (offset/limit)
3. **Texto completo**: `/texto` (XML) por norma, articulo por articulo
4. **Grafo de relaciones**: `/analisis` por norma para construir red de referencias
5. **Datos auxiliares**: Cargar `/datos-auxiliares/*` como tablas lookup
6. **Incremental**: Usar `fecha_actualizacion` para detectar cambios

---

## 3. PUERTAS GIRATORIAS (Cargos Publicos + Empresas)

### 3.1 Fuentes de Cargos Publicos (Quien fue cargo publico)

| Fuente | Formato | Cobertura | Estado | Prioridad |
|--------|---------|-----------|--------|-----------|
| **BOE Seccion II.A** (nombramientos/ceses) | XML via API | Todos los nombramientos nacionales | FUNCIONA | ALTA |
| **Senado XML** | XML/CSV | Todos los senadores desde 1977 | FUNCIONA | MEDIA |
| **Aragon datos abiertos** | JSON | 584+ registros desde 2013 | FUNCIONA | ALTA |
| **Cataluña transparencia** | CSV/JSON/XML | Altos cargos actualizacion diaria | FUNCIONA | ALTA |
| **Murcia datos abiertos** | CSV/JSON/XML | Altos cargos desde 2014 | FUNCIONA | ALTA |
| **Navarra datos abiertos** | CSV/JSON/XLSX | Cargos de gobierno + retribuciones | FUNCIONA | MEDIA |
| **Euskadi datos abiertos** | JSON/CSV/XML | Catalogo cargos publicos | FUNCIONA | MEDIA |
| **Barcelona diputacion** | JSON/XML/CSV/RDF | Cargos electos municipales | FUNCIONA | MEDIA |
| Congreso datos abiertos | - | Diputados | 403 BLOQUEADO | - |
| Oficina Conflictos Intereses | - | Resoluciones puertas giratorias | SSL EXPIRADO | - |
| Portal Transparencia | - | Altos cargos, declaraciones | 404 (URLs cambiadas) | - |
| CNMC Registro Lobbies | - | Lobistas | 403 | - |

**API Aragon (ejemplo funcional)**:
```
https://www.boa.aragon.es/cgi-bin/EBOA/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATABOAJSON&SORT=-PUBL&MATE-C=06NAC
```
Campos: nombre persona, cargo, fechas nombramiento/cese, autoridad, instrumento legal.

**BOE Seccion II.A**: Nombres de personas en texto libre dentro del XML. Requiere NLP para extraer nombres y cargos del texto de la resolucion.

### 3.2 Fuentes de Cargos Societarios (Quien dirige que empresa)

| Fuente | Formato | Cobertura | Estado | Prioridad |
|--------|---------|-----------|--------|-----------|
| **BORME PDFs** (boe.es/diario_borme) | PDF diario por provincia | Todos los nombramientos/ceses societarios desde 2009 | FUNCIONA | CRITICA |
| **gormeparser** (Go, GitHub) | Parser BORME → JSON | Extrae empresa, persona, rol, CIF (Seccion C) | Actualizado hace 21 dias | CRITICA |
| **bormeparser** (Python, GitHub) | Parser BORME → JSON | Extrae empresa, persona, rol | Archivado 2024, funcional | ALTERNATIVA |
| OpenCorporates API | JSON | Empresas españolas | 401 (requiere pago, 500 GBP/mes) | NO |
| Registro Mercantil Central | Web | Consultas por empresa/NIF | 1-9 EUR/consulta | NO |

**BORME estructura por entrada:**
- Nombre empresa (sin NIF en Seccion A)
- Tipo acto: Constitucion, Nombramientos, Ceses/Dimisiones, Revocaciones
- Nombre persona + rol: Adm. Unico, Adm. Solidario, Consejero, Secretario, Presidente, Apoderado
- Datos registrales y fechas
- Provincia
- CIF disponible en Seccion C para algunos actos

**gormeparser** (Go): La mejor opcion. Salida JSON, extrae CIFs de Seccion C, mantenido activamente. Se puede integrar como CLI llamado desde Laravel.

### 3.3 Arquitectura propuesta

```
CAPA 1: Cargos Publicos              CAPA 2: Cargos Societarios         CAPA 3: Contratos
(BOE II.A + CCAA datos abiertos)     (BORME PDFs + gormeparser)         (tu BD actual)

  persona_nombre                       persona_nombre                    empresa.nombre
  cargo                                empresa_nombre                    empresa.nif
  institucion                          rol (Adm, Consejero...)           adjudicacion.importe
  fecha_inicio                         fecha_nombramiento                fecha_adjudicacion
  fecha_cese                           provincia
                    \                        |                        /
                     v                       v                       v
              ┌──────────────────────────────────────────────────────────┐
              │  MOTOR DE CRUCE                                         │
              │  Match: nombre_cargo_publico ≈ nombre_cargo_societario  │
              │  Where: empresa_borme ≈ empresa.nombre                  │
              │  And:   fecha_cese_cargo < fecha_nombramiento_borme     │
              │  Result: "Persona X dejo cargo Y, entro en empresa Z,  │
              │           que recibio N contratos por M euros"           │
              └──────────────────────────────────────────────────────────┘
```

### 3.4 Tablas nuevas necesarias

```
cargos_publicos
  - id, nombre, apellidos, cargo, institucion, fecha_inicio, fecha_fin, fuente, source_id

cargos_societarios
  - id, nombre, apellidos, empresa_nombre, empresa_cif, rol, fecha, tipo_acto, provincia, borme_id

empresa_cargos (pivot → match con empresas existentes)
  - cargo_societario_id, empresa_id (FK), confidence_score

alertas_puertas_giratorias
  - cargo_publico_id, cargo_societario_id, empresa_id
  - tiempo_entre_cese_y_nombramiento (dias)
  - importe_contratos_empresa (pre-calculado)
  - estado (pendiente_revision, confirmado, descartado)
```

### 3.5 Retos tecnicos

1. **BORME en PDF**: Requiere parser (gormeparser en Go recomendado)
2. **BOE nombramientos en texto libre**: Requiere NLP para extraer nombres
3. **Matching de nombres**: Fuzzy matching con apellidos compuestos españoles (Levenshtein, Soundex español)
4. **Sin NIF en BORME Seccion A**: Match empresa por nombre (fuzzy)
5. **Datos fragmentados**: No hay BD unica nacional de cargos publicos, hay que agregar de 17 CCAA
6. **GDPR/LOPD**: Datos publicos (BORME, BOE) + interes publico = legal, pero requiere evaluacion de impacto

---

## 4. RESUMEN EJECUTIVO - PRIORIDADES

### Fase 1: Fuentes que FUNCIONAN ya (implementar primero)

| Modulo | Fuente | Esfuerzo | Impacto |
|--------|--------|----------|---------|
| Presupuestos municipales | Gobierto S3 bubbles.json | BAJO | 8.119 municipios |
| Legislacion | BOE Open Data API | MEDIO | 30.000+ normas |
| Cargos CCAA | datos.gob.es (Aragon, Cataluña, Murcia...) | BAJO | Cientos de registros |
| Senado | XML descargable | BAJO | Todos desde 1977 |

### Fase 2: Requieren parsing/scraping

| Modulo | Fuente | Esfuerzo | Impacto |
|--------|--------|----------|---------|
| BORME (cargos societarios) | PDFs + gormeparser | ALTO | Todos los nombramientos empresariales |
| PGE nacional | IGAE/SEPG XLSX | MEDIO | Presupuestos nacionales |
| BOE nombramientos | XML + NLP | ALTO | Todos los nombramientos nacionales |

### Fase 3: Motor de cruce (puertas giratorias)

| Componente | Dependencia | Esfuerzo |
|------------|------------|----------|
| Fuzzy matching nombres | Fases 1+2 completadas | MEDIO |
| Cruce con adjudicaciones | Matching + BD actual | MEDIO |
| Dashboard alertas | Todo lo anterior | BAJO |
