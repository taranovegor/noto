import type { ValidationErrorResponse, ValidationViolation, ApiErrorResponse } from '../types/api';

interface ParseErrorResult {
  message: string;
  violations: ValidationViolation[];
}

interface FetchError {
  status: number | string;
  data?: unknown;
  error?: string;
}

function isValidationError(data: unknown): data is ValidationErrorResponse {
  if (!data || typeof data !== 'object') return false;
  const d = data as Record<string, unknown>;
  return d.status === 422 && Array.isArray(d.violations);
}

function isApiError(data: unknown): data is ApiErrorResponse {
  if (!data || typeof data !== 'object') return false;
  return typeof (data as ApiErrorResponse).detail === 'string';
}

function isFetchError(err: unknown): err is FetchError {
  return typeof err === 'object' && err !== null && 'status' in err;
}

export function parseError(err: unknown): ParseErrorResult {
  if (!err || typeof err !== 'object') {
    return { message: 'An error occurred', violations: [] };
  }

  const e = err as Record<string, unknown>;

  if (isFetchError(err)) {
    if (err.status === 422 && isValidationError(err.data)) {
      const detail = err.data.detail || 'Validation failed';
      return { message: detail, violations: err.data.violations };
    }

    if (isApiError(err.data)) {
      return { message: err.data.detail!, violations: [] };
    }

    if (err.status === 'FETCH_ERROR') {
      return { message: 'Connection lost. Please check your network.', violations: [] };
    }

    if (err.status === 'TIMEOUT_ERROR') {
      return { message: 'Request timed out. Please try again.', violations: [] };
    }

    if (err.status === 401) {
      return { message: 'Authentication required. Please log in again.', violations: [] };
    }

    if (err.status === 403) {
      return { message: 'You do not have permission to perform this action.', violations: [] };
    }

    if (typeof err.status === 'number' && err.status >= 500) {
      return { message: 'Server error. Please try again later.', violations: [] };
    }
  }

  if (typeof e.message === 'string') {
    return { message: e.message, violations: [] };
  }

  return { message: 'An error occurred', violations: [] };
}
