export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

export interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export interface ValidationViolation {
  propertyPath: string;
  title: string;
  template: string;
  parameters: Record<string, string>;
}

export interface ValidationErrorResponse {
  type: string;
  title: string;
  status: number;
  detail: string;
  violations: ValidationViolation[];
}

export interface ApiErrorResponse {
  detail?: string;
}
