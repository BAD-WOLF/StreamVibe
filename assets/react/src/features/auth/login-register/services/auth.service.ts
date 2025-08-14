import type {LoginPayload, LoginResponse, RegisterPayload, RegisterResponse,} from "@shared/types/auth";

const API = import.meta.env.VITE_BACKEND_API_DOMAIN;

/**
 * Perform user login and receive JWT + refresh token
 */
export async function loginService(
    payload: LoginPayload
): Promise<LoginResponse> {
    const res = await fetch(`${API}/api/login_check`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const errorData = await res.json();
        throw new Error(errorData.message || "Login failed");
    }

    return res.json();
}

/**
 * Register a new user and receive confirmation message
 */
export async function registerService(
    payload: RegisterPayload
): Promise<RegisterResponse> {
    const res = await fetch(`${API}/api/register`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const errorData = await res.json();
        throw new Error(
            errorData.message || "Registration failed, if you received a email confirmation you can confirm then");
    }

    return res.json();
}

/**
 * Refresh JWT using a valid refresh token.
 * Supports single-use or multi-use tokens per LexikJWTRefreshTokenBundle.
 */
export async function refreshTokenService(
    refreshToken: string
): Promise<{ token: string; refresh_token: string }> {
    const res = await fetch(`${API}/api/token/refresh`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({refresh_token: refreshToken}),
    });

    if (!res.ok) {
        const errorData = await res.json();
        throw new Error(errorData.message || "Token refresh failed");
    }

    return res.json();
}
