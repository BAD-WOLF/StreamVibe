import type {LoginPayload, LoginResponse, RegisterPayload, RegisterResponse,} from "@shared/types/auth";
import { t } from "i18next";

const VITE_BACKEND_API_DOMAIN: string = import.meta.env.VITE_BACKEND_API_DOMAIN;

/**
 * Perform user login and receive JWT + refresh token
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
export async function loginService(
    _locale: string,
    payload: LoginPayload
): Promise<LoginResponse> {
    const res: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/api/login_check`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const errorData: any = await res.json();
        throw new Error(errorData.message || t("Login failed"));
    }

    return res.json();
}

/**
 * Register a new user and receive confirmation message
 */
export async function registerService(
    locale: string,
    payload: RegisterPayload
): Promise<RegisterResponse> {
    const res: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/${locale}/api/register`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const errorData: any = await res.json();
        throw new Error(
            errorData.message || t("Registration failed, if you received a email confirmation you can confirm then")
        );
    }

    return res.json();
}

/**
 * Refresh JWT using a valid refresh token.
 * Supports single-use or multi-use tokens per LexikJWTRefreshTokenBundle.
 */
export async function refreshTokenService(
    locale: string,
    refreshToken: string
): Promise<{ token: string; refresh_token: string }> {
    const res: Response = await fetch(`${VITE_BACKEND_API_DOMAIN}/${locale}/api/token/refresh`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({refresh_token: refreshToken}),
    });

    if (!res.ok) {
        const errorData: any = await res.json();
        throw new Error(errorData.message || t("Token refresh failed"));
    }

    return res.json();
}
