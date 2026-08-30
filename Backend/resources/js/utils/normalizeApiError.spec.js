import { describe, it, expect } from 'vitest';
import { normalizeApiError } from './normalizeApiError';

describe('normalizeApiError', () => {
    it('extracts message and fieldErrors from a Laravel 422 validation error', () => {
        const error = {
            response: {
                status: 422,
                data: {
                    message: 'The given data was invalid.',
                    errors: {
                        email: ['The email field is required.'],
                        password: ['The password must be at least 8 characters.'],
                    },
                },
            },
        };

        const result = normalizeApiError(error);

        expect(result.message).toBe('The given data was invalid.');
        expect(result.status).toBe(422);
        expect(result.isNetworkError).toBe(false);
        expect(result.fieldErrors).toEqual({
            email: ['The email field is required.'],
            password: ['The password must be at least 8 characters.'],
        });
        expect(result.fieldError('email')).toBe('The email field is required.');
        expect(result.fieldError('password')).toBe('The password must be at least 8 characters.');
        expect(result.fieldError('nonexistent_field')).toBeNull();
    });

    it('extracts message from a generic 404/403/500-style error with no errors object', () => {
        const error = {
            response: {
                status: 404,
                data: {
                    message: 'Resource not found.',
                },
            },
        };

        const result = normalizeApiError(error);

        expect(result.message).toBe('Resource not found.');
        expect(result.status).toBe(404);
        expect(result.isNetworkError).toBe(false);
        expect(result.fieldErrors).toEqual({});
        expect(result.fieldError('anything')).toBeNull();
    });

    it('falls back to the provided fallback message on a network error (no response at all)', () => {
        const error = { message: 'Network Error' }; // axios shape when error.response is undefined

        const result = normalizeApiError(error, 'Custom fallback message');

        expect(result.message).toBe('Custom fallback message');
        expect(result.status).toBeNull();
        expect(result.isNetworkError).toBe(true);
        expect(result.fieldErrors).toEqual({});
    });

    it('falls back to the default i18n message when no fallback is provided and there is a network error', () => {
        const error = { message: 'Network Error' };

        const result = normalizeApiError(error);

        // No hardcoded fallback passed -> uses i18n.global.t('common.unexpected_error_retry'),
        // which must resolve to a non-empty, translated string (not the raw key).
        expect(typeof result.message).toBe('string');
        expect(result.message.length).toBeGreaterThan(0);
        expect(result.message).not.toBe('common.unexpected_error_retry');
    });

    it('falls back to the provided fallback when the response body has no message field', () => {
        const error = {
            response: {
                status: 500,
                data: {},
            },
        };

        const result = normalizeApiError(error, 'Server blew up');

        expect(result.message).toBe('Server blew up');
        expect(result.status).toBe(500);
        expect(result.isNetworkError).toBe(false);
    });

    it('handles a response with a completely missing data body gracefully', () => {
        const error = {
            response: {
                status: 500,
            },
        };

        const result = normalizeApiError(error, 'Fallback for missing body');

        expect(result.message).toBe('Fallback for missing body');
        expect(result.status).toBe(500);
        expect(result.isNetworkError).toBe(false);
        expect(result.fieldErrors).toEqual({});
    });

    it('handles a completely undefined/null error object without throwing', () => {
        expect(() => normalizeApiError(undefined, 'fallback')).not.toThrow();
        expect(normalizeApiError(undefined, 'fallback').message).toBe('fallback');
        expect(normalizeApiError(null, 'fallback').message).toBe('fallback');
    });
});
