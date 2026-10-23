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

Todas las llamadas a `/scores` deben autenticarse mediante la clave de API del ecosistema ScoreBox.

### Cabeceras HTTP Requeridas:

| Cabecera | Tipo | Obligatorio | Ejemplo | Descripción |
| :--- | :--- | :---: | :--- | :--- |
| `X-ScoreBox-Api-Key` | `String` | **Sí** | `SCOREBOX_SECRET_KEY` | Clave secreta de la API configurada en el servidor. Alternativamente se admite `Authorization: Bearer <API_KEY>`. |
| `Accept` | `String` | **Sí** | `application/json` | Obligatorio para recibir respuestas y errores formateados en JSON. |
| `X-User-Is-Premium` | `String` | Opcional | `true` o `false` | Indica si el usuario de la app móvil tiene suscripción PRO activa. Necesario para descargar obras marcadas como exclusivas PRO. |

---

## 3. Endpoints de la API

### 3.1. Listar Catálogo de Partituras (`GET /scores`)

Obtiene una lista paginada y filtrable de las obras publicadas y activas.

#### Parámetros de consulta (Query Params):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :---: | :--- |
| `search` | `string` | - | Búsqueda parcial de texto en título, compositor, instrumento o género. |
| `instrument` | `string` | - | Filtro por instrumento o agrupación (ej. `Clarinete`, `Piano`, `Banda`). |
| `genre` | `string` | - | Filtro por género/estilo (ej. `Marcha`, `Clásica`, `Pasodoble`). |
| `difficulty` | `string` | - | Nivel de dificultad: `easy` (fácil), `medium` (intermedio), `hard` (avanzado). |
| `is_premium_only` | `boolean` | - | `true` para ver solo exclusivas PRO; `false` para ver solo libres/gratuitas. |
| `sort_by` | `string` | `id` | Campo de ordenación: `id`, `title`, `composer`, `downloads_count`, `file_size`. |
| `order` | `string` | `desc` | Dirección del orden: `asc` o `desc`. |
| `per_page` | `integer`| `20` | Cantidad de registros por página (mínimo 1, máximo 100). |
| `page` | `integer`| `1` | Número de página solicitada. |

#### Ejemplo de Petición:
```http
GET /api/v1/scores?instrument=Clarinete&search=Bach&page=1 HTTP/1.1
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
      "title": "Canon en Re Mayor",
      "composer": "Johann Pachelbel",
      "instrument": "Clarinete en Sib",
      "genre": "Clásica",
      "difficulty": "medium",
      "description": "Arreglo para clarinete solista con acompañamiento.",
      "file_name": "canon_in_d_clarinet.pdf",
      "file_size": 245760,
      "formatted_file_size": "240 KB",
      "mime_type": "application/pdf",
      "is_premium_only": false,
      "downloads_count": 42,
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

Devuelve la información técnica y metadatos de una partitura en concreto.

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
    "title": "Canon en Re Mayor",
    "composer": "Johann Pachelbel",
    "instrument": "Clarinete en Sib",
    "genre": "Clásica",
    "difficulty": "medium",
    "description": "Arreglo para clarinete solista con acompañamiento.",
    "file_name": "canon_in_d_clarinet.pdf",
    "file_size": 245760,
    "formatted_file_size": "240 KB",
    "mime_type": "application/pdf",
    "is_premium_only": false,
    "downloads_count": 42,
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
  1. Verifica que la obra exista y esté en estado `is_active: true`.
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
  * `Content-Disposition: attachment; filename="canon_in_d_clarinet.pdf"`
  * `Cache-Control: no-cache, must-revalidate`
* **Cuerpo:** Bytes en streaming del archivo.

---

## 4. Códigos de Estado HTTP y Errores

| Código HTTP | Nombre | Causa común |
| :---: | :--- | :--- |
| `200 OK` | Correcto | Operación completada con éxito. |
| `401 Unauthorized` | No Autorizado | No se envió la cabecera `X-ScoreBox-Api-Key` o la clave es incorrecta. |
| `403 Forbidden` | Prohibido | La obra es exclusiva para usuarios PRO (`is_premium_only: true`) y el cliente envió `X-User-Is-Premium: false`. |
| `404 Not Found` | No Encontrado | El ID no existe, la obra está desactivada en el panel o el archivo físico no existe en disco. |
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

data class ScoreDocumentDto(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("composer") val composer: String?,
    @SerializedName("instrument") val instrument: String?,
    @SerializedName("genre") val genre: String?,
    @SerializedName("difficulty") val difficulty: String?,
    @SerializedName("description") val description: String?,
    @SerializedName("file_name") val fileName: String,
    @SerializedName("file_size") val fileSize: Long,
    @SerializedName("formatted_file_size") val formattedFileSize: String,
    @SerializedName("mime_type") val mimeType: String,
    @SerializedName("is_premium_only") val isPremiumOnly: Boolean,
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

import com.scorebox.app.data.api.model.ScoreCatalogResponse
import com.scorebox.app.data.api.model.ScoreDetailResponse
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
        @Query("instrument") instrument: String? = null,
        @Query("genre") genre: String? = null,
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
* **Modelo Eloquent:** [`app/Models/ScoreDocument.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Models/ScoreDocument.php)
* **Recurso Filament Admin:** [`app/Filament/Resources/ScoreDocuments/ScoreDocumentResource.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/app/Filament/Resources/ScoreDocuments/ScoreDocumentResource.php)
* **Suite de Pruebas:** [`tests/Feature/ScoreDocuments/ScoreDocumentApiTest.php`](file:///c:/Users/pable/Documents/Webs/Score/aministrador/tests/Feature/ScoreDocuments/ScoreDocumentApiTest.php)
