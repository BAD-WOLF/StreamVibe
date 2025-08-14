import type {
    RepasswordRequestPayload,
    RepasswordRequestResponse,
    RepasswordValidateResponse,
    RepasswordValidatePayload,
    RepasswordResetPayload,
    RepasswordResetResponse
} from "@shared/types/auth";

const VITE_BACKEND_API_DOMAIN: string = import.meta.env.VITE_BACKEND_API_DOMAIN;

/**
 * Send email recovery
 */
export async function requestPasswordReset(
    payload: RepasswordRequestPayload
): Promise<RepasswordRequestResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/reset-password/request`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });
    if (!resp.ok) throw new Error("Erro ao solicitar reset de senha");
    return resp.json();
}

/**
 * Validate the token resaved by email
 */
export async function validateResetToken(
    payload: RepasswordValidatePayload
): Promise<RepasswordValidateResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/reset-password/validate/${payload.token}`);
    if (!resp.ok) throw new Error("Token inválido ou expirado");
    return resp.json();
}

/**
 * Send a new pass along with the token
 */
export async function resetPassword(
    payload: RepasswordResetPayload
): Promise<RepasswordResetResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/reset-password/reset`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });
    if (!resp.ok) throw new Error("Erro ao redefinir senha");
    return resp.json();
}
