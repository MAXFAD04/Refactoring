use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde::Serialize;
use std::fmt;

/// Единый формат ошибок для всего приложения
/// Всегда возвращает HTTP 200 с ok: false для предсказуемости
#[derive(Debug, Serialize)]
pub struct ApiError {
    pub ok: bool,
    pub error: ErrorDetails,
}

#[derive(Debug, Serialize)]
pub struct ErrorDetails {
    pub code: String,
    pub message: String,
    pub trace_id: String,
}

impl ApiError {
    pub fn new(code: impl Into<String>, message: impl Into<String>) -> Self {
        Self {
            ok: false,
            error: ErrorDetails {
                code: code.into(),
                message: message.into(),
                trace_id: uuid::Uuid::new_v4().to_string(),
            },
        }
    }

    pub fn database(message: impl Into<String>) -> Self {
        Self::new("DATABASE_ERROR", message)
    }

    pub fn upstream(status: u16, message: impl Into<String>) -> Self {
        Self::new(format!("UPSTREAM_{}", status), message)
    }

    pub fn not_found(message: impl Into<String>) -> Self {
        Self::new("NOT_FOUND", message)
    }

    pub fn internal(message: impl Into<String>) -> Self {
        Self::new("INTERNAL_ERROR", message)
    }

    pub fn validation(message: impl Into<String>) -> Self {
        Self::new("VALIDATION_ERROR", message)
    }
}

impl fmt::Display for ApiError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        write!(
            f,
            "ApiError[{}]: {} (trace: {})",
            self.error.code, self.error.message, self.error.trace_id
        )
    }
}

impl std::error::Error for ApiError {}

/// Всегда возвращаем HTTP 200 с ok: false
impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        (StatusCode::OK, Json(self)).into_response()
    }
}

/// Конвертация sqlx ошибок
impl From<sqlx::Error> for ApiError {
    fn from(err: sqlx::Error) -> Self {
        tracing::error!("Database error: {:?}", err);
        ApiError::database(err.to_string())
    }
}

/// Конвертация reqwest ошибок
impl From<reqwest::Error> for ApiError {
    fn from(err: reqwest::Error) -> Self {
        tracing::error!("HTTP client error: {:?}", err);
        
        if let Some(status) = err.status() {
            ApiError::upstream(status.as_u16(), err.to_string())
        } else {
            ApiError::internal(format!("HTTP client error: {}", err))
        }
    }
}

/// Конвертация anyhow ошибок
impl From<anyhow::Error> for ApiError {
    fn from(err: anyhow::Error) -> Self {
        tracing::error!("Internal error: {:?}", err);
        ApiError::internal(err.to_string())
    }
}

/// Успешный ответ с ok: true
#[derive(Debug, Serialize)]
pub struct ApiSuccess<T: Serialize> {
    pub ok: bool,
    #[serde(flatten)]
    pub data: T,
}

impl<T: Serialize> ApiSuccess<T> {
    pub fn new(data: T) -> Self {
        Self { ok: true, data }
    }
}

/// Тип результата для всех хендлеров
pub type ApiResult<T> = Result<Json<ApiSuccess<T>>, ApiError>;

/// Хелпер для создания успешного ответа
pub fn ok<T: Serialize>(data: T) -> ApiResult<T> {
    Ok(Json(ApiSuccess::new(data)))
}

