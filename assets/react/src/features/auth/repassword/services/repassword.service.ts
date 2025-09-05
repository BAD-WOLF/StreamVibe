import type {
    RepasswordRequestPayload,
    RepasswordRequestResponse,
    RepasswordValidateResponse,
    RepasswordValidatePayload,
    RepasswordResetPayload,
    RepasswordResetResponse
} from "@shared/types/auth";
import { t } from "i18next";

const VITE_BACKEND_API_DOMAIN: string = import.meta.env.VITE_BACKEND_API_DOMAIN;

/**
 * Send email recovery
 */
export async function requestPasswordReset(
    locale: string,
    payload: RepasswordRequestPayload
): Promise<RepasswordRequestResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/${locale}/api/reset-password/request`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });
    if (!resp.ok) throw new Error(t("Failed to send reset email"));
    return resp.json();
}

/**
 * Validate the token resaved by email
 */
export async function validateResetToken(
    locale: string,
    payload: RepasswordValidatePayload
): Promise<RepasswordValidateResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/${locale}/api/reset-password/validate/${payload.token}`);
    if (!resp.ok) throw new Error(t("Invalid token or expired"));
    return resp.json();
}

/**
 * Send a new pass along with the token
 */
export async function resetPassword(
    locale: string,
    payload: RepasswordResetPayload
): Promise<RepasswordResetResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/${locale}/api/reset-password/reset`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });
    if (!resp.ok) throw new Error(t("Failed to reset password"));
    return resp.json();
}
