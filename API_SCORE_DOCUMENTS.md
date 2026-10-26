# Documentación de la API de Partituras y Catálogo de Documentos (ScoreBox)

Guía técnica de integración para clientes móviles (App Android / iOS) y desarrolladores/agentes que consuman la API REST de ScoreBox.

---

## 1. Información General y Entorno

* **Base URL (Producción):** `https://admin.scorebox.pro/api/v1`
* **Protocolo:** `HTTPS` (Obligatorio)
* **Formato de intercambio:** `JSON (UTF-8)` para listados y detalles; `Flujo binario (Stream)` para descargas de archivos.
* **Limitación de tasa (Rate Limiting):** `60 peticiones/minuto` por IP cliente.

---

## 2. Autenticación y Cabeceras Globales

Todas las llamadas a `/scores`, `/genres` y `/band-types` deben autenticarse mediante la clave de API del ecosistema ScoreBox.

### Cabeceras HTTP Requeridas:

| Cabecera | Tipo | Obligatorio | Ejemplo | Descripción |
| :--- | :--- | :---: | :--- | :--- |
| `X-ScoreBox-Api-Key` | `String` | **Sí** | `SCOREBOX_SECRET_KEY` | Clave secreta de la API configurada en el servidor. Alternativamente se admite `Authorization: Bearer <API_KEY>`. |
| `Accept` | `String` | **Sí** | `application/json` | Obligatorio para recibir respuestas y errores formateados en JSON. |
| `X-User-Is-Premium` | `String` | Opcional | `true` o `false` | Indica si el usuario de la app móvil tiene suscripción PRO activa. Necesario para descargar obras marcadas como exclusivas PRO. |

---

## 3. Endpoints de la API

### 3.1. Listar Catálogo de Partituras (`GET /scores`)

Obtiene una lista paginada y filtrable de las obras actualmente publicadas y vigentes (respetando fechas de inicio y fin de publicación).

#### Parámetros de consulta (Query Params):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :---: | :--- |
| `search` | `string` | - | Búsqueda parcial de texto en título, compositor, autor, instrumento, género o agrupación. |
| `sku` | `string` | - | Filtro exacto por SKU / Product ID de Google Play Store (ej. `score_canon_in_d`). |
| `author_id` | `int` | - | Filtro exacto por ID del autor o compositor. |
| `author` | `string` | - | Filtro por nombre o slug del autor / compositor (ej: `manuel-de-falla`, `beethoven`). |
| `music_genre_id`| `int` | - | Filtro exacto por ID de género musical. |
| `genre` | `string` | - | Filtro por slug o nombre de género musical (ej: `pasodoble`, `marcha-procesional`). |
| `band_type_id` | `int` | - | Filtro exacto por ID de tipo de banda / agrupación. |
| `band_type` | `string` | - | Filtro por slug o nombre de tipo de banda (ej: `banda-de-musica`, `charanga`). |
| `instrument` | `string` | - | Filtro por instrumento o papel específico (ej. `Clarinete 1º`, `Piano`, `Guion`). |
| `difficulty` | `string` | - | Nivel de dificultad: `easy` (fácil), `medium` (intermedio), `hard` (avanzado). |
| `is_premium_only` | `boolean` | - | `true` para ver solo exclusivas PRO; `false` para ver solo libres/gratuitas. |
| `sort_by` | `string` | `id` | Campo de ordenación: `id`, `title`, `composer`, `downloads_count`, `file_size`, `price`, `published_at`. |
| `order` | `string` | `desc` | Dirección del orden: `asc` o `desc`. |
| `per_page` | `integer`| `20` | Cantidad de registros por página (mínimo 1, máximo 100). |
| `page` | `integer`| `1` | Número de página solicitada. |

#### Ejemplo de Petición:
```http
GET /api/v1/scores?genre=pasodoble&band_type=banda-de-musica&page=1 HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Amparito Roca",
      "sku": "score_amparito_roca",
      "author_id": 4,
      "composer": "Jaime Texidor",
      "author": {
        "id": 4,
        "name": "Jaime Texidor",
        "slug": "jaime-texidor",
        "country": "España",
        "photo_url": "https://admin.scorebox.pro/storage/authors/texidor.jpg",
        "birth_year": 1884,
        "death_year": 1957,
        "website_url": "https://jaimetexidor.com",
        "spotify_url": "https://open.spotify.com/artist/...",
        "youtube_url": "https://youtube.com/@jaimetexidor",
        "instagram_url": "https://instagram.com/jaimetexidor",
        "is_featured": true
      },
      "instrument": "Partitura Completa / Score",
      "genre": "Pasodoble",
      "music_genre": {
        "id": 2,
        "name": "Pasodoble",
        "slug": "pasodoble"
      },
      "band_type": {
        "id": 1,
        "name": "Banda de Música",
        "slug": "banda-de-musica"
      },
      "difficulty": "medium",
      "description": "Pasodoble clásico de concierto y desfile.",
      "price": "0.00",
      "file_name": "amparito_roca_score.pdf",
      "file_size": 245760,
      "formatted_file_size": "240 KB",
      "mime_type": "application/pdf",
      "is_premium_only": false,
      "published_at": "2026-10-01T00:00:00+02:00",
      "publication_end_at": null,
      "downloads_count": 87,
      "download_url": "https://admin.scorebox.pro/api/v1/scores/1/download",
      "created_at": "2026-10-23T10:35:00+02:00",
      "updated_at": "2026-10-23T10:35:00+02:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 54
  }
}
```

---

### 3.2. Detalle de una Partitura (`GET /scores/{id}`)

Devuelve la información técnica y metadatos completos de una partitura en concreto.

#### Ejemplo de Petición:
```http
GET /api/v1/scores/1 HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Amparito Roca",
    "sku": "score_amparito_roca",
    "composer": "Jaime Texidor",
    "instrument": "Partitura Completa / Score",
    "genre": "Pasodoble",
    "music_genre": {
      "id": 2,
      "name": "Pasodoble",
      "slug": "pasodoble"
    },
    "band_type": {
      "id": 1,
      "name": "Banda de Música",
      "slug": "banda-de-musica"
    },
    "difficulty": "medium",
    "description": "Pasodoble clásico de concierto y desfile.",
    "price": "0.00",
    "file_name": "amparito_roca_score.pdf",
    "file_size": 245760,
    "formatted_file_size": "240 KB",
    "mime_type": "application/pdf",
    "is_premium_only": false,
    "published_at": "2026-10-01T00:00:00+02:00",
    "publication_end_at": null,
    "downloads_count": 87,
    "download_url": "https://admin.scorebox.pro/api/v1/scores/1/download",
    "created_at": "2026-10-23T10:35:00+02:00",
    "updated_at": "2026-10-23T10:35:00+02:00"
  }
}
```

---

### 3.3. Descarga del Archivo Binario (`GET /scores/{id}/download`)

Inicia la descarga directa y segura en streaming del archivo físico (PDF, MusicXML, ZIP, audio, etc.).

* **Comportamiento del Servidor:**
  1. Verifica que la obra exista y esté vigente (`is_active: true`, `published_at <= now()`, `publication_end_at >= now()`).
  2. Si `is_premium_only: true`, comprueba la cabecera `X-User-Is-Premium: true`. Si el valor es `false`, deniega la descarga con `403 Forbidden`.
  3. Verifica la existencia física del archivo en el almacenamiento privado del servidor.
  4. Incrementa automáticamente el contador `downloads_count` en la base de datos.
  5. Transmite el archivo binario con cabeceras `Content-Type` correspondiente y `Content-Disposition: attachment; filename="..."`.

#### Ejemplo de Petición:
```http
GET /api/v1/scores/1/download HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
X-User-Is-Premium: true
```

#### Respuesta Exitosa (`200 OK`):
* **Cabeceras devueltas:**
  * `Content-Type: application/pdf` (o el MIME type correspondiente).
  * `Content-Disposition: attachment; filename="amparito_roca_score.pdf"`
  * `Cache-Control: no-cache, must-revalidate`
* **Cuerpo:** Bytes en streaming del archivo.

---

### 3.4. Listar Géneros Musicales (`GET /genres`)

Devuelve todos los géneros musicales activos configurados en el panel para rellenar filtros o pestañas en la app.

#### Ejemplo de Petición:
```http
GET /api/v1/genres HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Marcha Procesional",
      "slug": "marcha-procesional",
      "description": "Marchas fúnebres, solemnes y de gloria para procesiones."
    },
    {
      "id": 2,
      "name": "Pasodoble",
      "slug": "pasodoble",
      "description": "Pasodobles de concierto, taurinos y de fiesta."
    },
    {
      "id": 3,
      "name": "Banda Sonora",
      "slug": "banda-sonora",
      "description": "Música de cine, televisión y bandas sonoras originales."
    }
  ]
}
```

---

### 3.5. Listar Tipos de Banda / Agrupaciones (`GET /band-types`)

Devuelve todos los tipos de agrupación musical configurados en el panel (Banda de Música, Charanga, Orquesta Sinfónica, etc.).

#### Ejemplo de Petición:
```http
GET /api/v1/band-types HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Banda de Música",
      "slug": "banda-de-musica",
      "description": "Banda de plantilla tradicional con maderas, metales y percusión."
    },
    {
      "id": 2,
      "name": "Charanga / Fanfarria",
      "slug": "charanga-fanfarria",
      "description": "Agrupación reducida y festiva de calle con metales, saxos y percusión."
    },
    {
      "id": 3,
      "name": "Orquesta Sinfónica",
      "slug": "orquesta-sinfonica",
      "description": "Orquesta completa con cuerda, viento y percusión."
    }
  ]
}
```

---

### 3.6. Listar Autores / Compositores (`GET /authors`)

Devuelve la lista de compositores y autores registrados en el catálogo de ScoreBox, con su biografía básica, enlaces y número de obras publicadas.

#### Parámetros de consulta (Query Params):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :---: | :--- |
| `search` | `string` | - | Búsqueda parcial de texto por nombre o país del autor. |
| `country` | `string` | - | Filtrar por país o nacionalidad exacta (ej: `España`). |
| `featured` | `boolean` | - | Filtrar solo autores destacados (`true` o `false`). |
| `per_page` | `integer`| `30` | Autores por página (máximo 100). |
| `page` | `integer`| `1` | Página solicitada. |

#### Ejemplo de Petición:
```http
GET /api/v1/authors?featured=true HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Manuel de Falla",
      "slug": "manuel-de-falla",
      "country": "España",
      "photo_url": "https://admin.scorebox.pro/storage/authors/falla.jpg",
      "birth_year": 1876,
      "death_year": 1946,
      "website_url": "https://manueldefalla.com",
      "spotify_url": "https://open.spotify.com/artist/...",
      "youtube_url": "https://youtube.com/@manueldefalla",
      "instagram_url": null,
      "social_links": {
        "wikipedia": "https://es.wikipedia.org/wiki/Manuel_de_Falla"
      },
      "bio": "Uno de los compositores españoles más importantes de la primera mitad del siglo XX...",
      "is_featured": true,
      "scores_count": 8
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 30,
    "total": 1
  }
}
```

---

### 3.7. Obtener Perfil de Autor con sus Obras (`GET /authors/{id}`)

Devuelve la ficha completa del compositor y el catálogo completo de sus partituras publicadas en ScoreBox.

#### Ejemplo de Petición:
```http
GET /api/v1/authors/1 HTTP/1.1
Host: admin.scorebox.pro
X-ScoreBox-Api-Key: TU_API_KEY_AQUI
Accept: application/json
```

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Manuel de Falla",
    "slug": "manuel-de-falla",
    "country": "España",
    "photo_url": "https://admin.scorebox.pro/storage/authors/falla.jpg",
    "birth_year": 1876,
    "death_year": 1946,
    "website_url": "https://manueldefalla.com",
    "spotify_url": "https://open.spotify.com/artist/...",
    "youtube_url": "https://youtube.com/@manueldefalla",
    "instagram_url": null,
    "social_links": {
      "wikipedia": "https://es.wikipedia.org/wiki/Manuel_de_Falla"
    },
    "bio": "Uno de los compositores españoles más importantes...",
    "is_featured": true,
    "scores_count": 2,
    "scores": [
      {
        "id": 10,
        "title": "El Amor Brujo - Danza Ritual del Fuego",
        "sku": "score_falla_fuego",
        "author_id": 1,
        "composer": "Manuel de Falla",
        "author": { ... },
        "instrument": "Banda Completa",
        "genre": "Música Clásica Española",
        "music_genre": { "id": 4, "name": "Clásica", "slug": "clasica" },
        "band_type": { "id": 1, "name": "Banda de Música", "slug": "banda-de-musica" },
        "difficulty": "hard",
        "is_premium_only": false,
        "price": "0.00",
        "download_url": "https://admin.scorebox.pro/api/v1/scores/10/download"
      }
    ]
  }
}
```

---

## 4. Códigos de Estado HTTP y Errores

| Código HTTP | Nombre | Causa común |
| :---: | :--- | :--- |
| `200 OK` | Correcto | Operación completada con éxito. |
| `401 Unauthorized` | No Autorizado | No se envió la cabecera `X-ScoreBox-Api-Key` o la clave es incorrecta. |
| `403 Forbidden` | Prohibido | La obra es exclusiva para usuarios PRO (`is_premium_only: true`) y el cliente envió `X-User-Is-Premium: false`. |
| `404 Not Found` | No Encontrado | El ID no existe, la obra está inactiva/fuera de fechas o el archivo físico no existe en disco. |
| `429 Too Many Requests` | Límite Superado | Se superó el umbral de 60 peticiones en 1 minuto. |

#### Estructura Estándar de Respuesta de Error (JSON):
```json
{
  "success": false,
  "message": "Este documento es exclusivo para usuarios con suscripción PRO."
}
```

---

## 5. Implementación de Referencia en Android (Kotlin)

### 5.1. Modelos de Datos (DTOs con Gson)
```kotlin
package com.scorebox.app.data.api.model

import com.google.gson.annotations.SerializedName

data class ScoreCatalogResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<ScoreDocumentDto>,
    @SerializedName("meta") val meta: PaginationMetaDto?
)

data class ScoreDetailResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: ScoreDocumentDto
)

data class MusicGenresResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<CategoryItemDto>
)

data class BandTypesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<CategoryItemDto>
)

data class CategoryItemDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("description") val description: String?
)

data class AuthorDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("country") val country: String?,
    @SerializedName("photo_url") val photoUrl: String?,
    @SerializedName("birth_year") val birthYear: Int?,
    @SerializedName("death_year") val deathYear: Int?,
    @SerializedName("website_url") val websiteUrl: String?,
    @SerializedName("spotify_url") val spotifyUrl: String?,
    @SerializedName("youtube_url") val youtubeUrl: String?,
    @SerializedName("instagram_url") val instagramUrl: String?,
    @SerializedName("social_links") val socialLinks: Map<String, String>?,
    @SerializedName("bio") val bio: String?,
    @SerializedName("is_featured") val isFeatured: Boolean,
    @SerializedName("scores_count") val scoresCount: Int?,
    @SerializedName("scores") val scores: List<ScoreDocumentDto>?
)

data class AuthorsListResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<AuthorDto>,
    @SerializedName("meta") val meta: PaginationMetaDto?
)

data class AuthorDetailResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AuthorDto
)

data class ScoreDocumentDto(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("sku") val sku: String?,
    @SerializedName("author_id") val authorId: Long?,
    @SerializedName("composer") val composer: String?,
    @SerializedName("author") val author: AuthorDto?,
    @SerializedName("instrument") val instrument: String?,
    @SerializedName("genre") val genre: String?,
    @SerializedName("music_genre") val musicGenre: CategoryItemDto?,
    @SerializedName("band_type") val bandType: CategoryItemDto?,
    @SerializedName("difficulty") val difficulty: String?,
    @SerializedName("description") val description: String?,
    @SerializedName("price") val price: String?,
    @SerializedName("file_name") val fileName: String,
    @SerializedName("file_size") val fileSize: Long,
    @SerializedName("formatted_file_size") val formattedFileSize: String,
    @SerializedName("mime_type") val mimeType: String,
    @SerializedName("is_premium_only") val isPremiumOnly: Boolean,
    @SerializedName("published_at") val publishedAt: String?,
    @SerializedName("publication_end_at") val publicationEndAt: String?,
    @SerializedName("downloads_count") val downloadsCount: Int,
    @SerializedName("download_url") val downloadUrl: String,
    @SerializedName("created_at") val createdAt: String?,
    @SerializedName("updated_at") val updatedAt: String?
)

data class PaginationMetaDto(
    @SerializedName("current_page") val currentPage: Int,
    @SerializedName("last_page") val lastPage: Int,
    @SerializedName("per_page") val perPage: Int,
    @SerializedName("total") val total: Int
)
```

### 5.2. Interfaz Retrofit
```kotlin
package com.scorebox.app.data.api

import com.scorebox.app.data.api.model.*
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Path
import retrofit2.http.Query
import retrofit2.http.Streaming

interface ScoreBoxCatalogApi {

    @GET("scores")
    suspend fun getScores(
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Query("search") search: String? = null,
        @Query("sku") sku: String? = null,
        @Query("author_id") authorId: Long? = null,
        @Query("author") authorSlugOrName: String? = null,
        @Query("genre") genreSlug: String? = null,
        @Query("music_genre_id") musicGenreId: Long? = null,
        @Query("band_type") bandTypeSlug: String? = null,
        @Query("band_type_id") bandTypeId: Long? = null,
        @Query("instrument") instrument: String? = null,
        @Query("difficulty") difficulty: String? = null,
        @Query("is_premium_only") isPremiumOnly: Boolean? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("order") order: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): Response<ScoreCatalogResponse>

    @GET("scores/{id}")
    suspend fun getScoreDetail(
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Path("id") scoreId: Long
    ): Response<ScoreDetailResponse>

    @GET("authors")
    suspend fun getAuthors(
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Query("search") search: String? = null,
        @Query("country") country: String? = null,
        @Query("featured") featured: Boolean? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30
    ): Response<AuthorsListResponse>

    @GET("authors/{id}")
    suspend fun getAuthorDetail(
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Path("id") authorId: Long
    ): Response<AuthorDetailResponse>
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Path("id") scoreId: Long
    ): Response<ScoreDetailResponse>

    @GET("genres")
    suspend fun getGenres(
        @Header("X-ScoreBox-Api-Key") apiKey: String
    ): Response<MusicGenresResponse>

    @GET("band-types")
    suspend fun getBandTypes(
        @Header("X-ScoreBox-Api-Key") apiKey: String
    ): Response<BandTypesResponse>

    @Streaming
    @GET("scores/{id}/download")
    suspend fun downloadScoreFile(
        @Header("X-ScoreBox-Api-Key") apiKey: String,
        @Header("X-User-Is-Premium") isPremium: Boolean,
        @Path("id") scoreId: Long
    ): Response<ResponseBody>
}
```

### 5.3. Utilidad para Guardar el Archivo en Almacenamiento Local
```kotlin
package com.scorebox.app.util

import okhttp3.ResponseBody
import java.io.File
import java.io.FileOutputStream
import java.io.InputStream

object FileDownloadHelper {

    fun saveFileToDisk(body: ResponseBody, destinationFile: File): Boolean {
        var inputStream: InputStream? = null
        var outputStream: FileOutputStream? = null

        return try {
            inputStream = body.byteStream()
            outputStream = FileOutputStream(destinationFile)
            val buffer = ByteArray(4096)
            var bytesRead: Int

            while (inputStream.read(buffer).also { bytesRead = it } != -1) {
                outputStream.write(buffer, 0, bytesRead)
            }
            outputStream.flush()
            true
        } catch (e: Exception) {
            destinationFile.delete()
            false
        } finally {
            inputStream?.close()
            outputStream?.close()
        }
    }
}
```

---

## 6. Referencia de Archivos en el Servidor (Backend Laravel)

Para cualquier ajuste en el backend del panel de administración:
* **Rutas API:** [`routes/api.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/routes/api.php)
* **Controlador:** [`app/Http/Controllers/Api/ScoreDocumentApiController.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Http/Controllers/Api/ScoreDocumentApiController.php)
* **Middleware de API Key:** [`app/Http/Middleware/ValidateScoreBoxApiKey.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Http/Middleware/ValidateScoreBoxApiKey.php)
* **Modelos Eloquent:** 
  * [`app/Models/ScoreDocument.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Models/ScoreDocument.php)
  * [`app/Models/Author.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Models/Author.php)
  * [`app/Models/MusicGenre.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Models/MusicGenre.php)
  * [`app/Models/BandType.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Models/BandType.php)
* **Recursos Filament Admin:** 
  * [`app/Filament/Resources/ScoreDocuments/ScoreDocumentResource.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Filament/Resources/ScoreDocuments/ScoreDocumentResource.php)
  * [`app/Filament/Resources/Authors/AuthorResource.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Filament/Resources/Authors/AuthorResource.php)
  * [`app/Filament/Resources/MusicGenres/MusicGenreResource.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Filament/Resources/MusicGenres/MusicGenreResource.php)
  * [`app/Filament/Resources/BandTypes/BandTypeResource.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Filament/Resources/BandTypes/BandTypeResource.php)
* **Seeder Inicial:** [`database/seeders/MusicGenreAndBandTypeSeeder.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/database/seeders/MusicGenreAndBandTypeSeeder.php)
* **Suite de Pruebas:** [`tests/Feature/ScoreDocuments/ScoreDocumentApiTest.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/tests/Feature/ScoreDocuments/ScoreDocumentApiTest.php)
