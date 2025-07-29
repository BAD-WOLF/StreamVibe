import type {RepasswordRequestPayload, RepasswordRequestResponse} from "@auth/repassword/types/RepasswordRequest.ts";
import type {RepasswordValidateResponse} from "@auth/repassword/types/RepasswordValidateResponse.ts";
import type {ResetPasswordPayload, ResetPasswordResponse,} from "@auth/repassword/types/ResetPasswordResponse.ts";

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
    token: string
): Promise<RepasswordValidateResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/reset-password/validate/${token}`);
    if (!resp.ok) throw new Error("Token inválido ou expirado");
    return resp.json();
}

/**
 * Send a new pass along with the token
 */
export async function resetPassword(
    payload: ResetPasswordPayload
): Promise<ResetPasswordResponse> {
    const resp: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/reset-password/reset`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });
    if (!resp.ok) throw new Error("Erro ao redefinir senha");
    return resp.json();
}
