// Visual component of the password strength bar
import { Trans } from "react-i18next";
import { t } from "i18next";

export default function PasswordStrengthBar({strength}: { strength: number; }) {
    const strengthLabel: Array<string> = [t("Very Weak"), t("Weak"), t("Medium"), t("Strong"), t("Excellent")];
    const strengthColor: Array<string> = ["bg-red-500", "bg-red-500", "bg-yellow-400", "bg-green-500", "bg-green-600"];

    return (
        <div className="mb-4">
            <div className="h-1 rounded-full bg-gray-200 overflow-hidden">
                <div
                    className={`h-full transition-all ${strengthColor[strength]}`}
                    style={{width: `${(strength / 4) * 100}%`}}
                />
            </div>
            <p className="text-xs text-gray-400 mt-1">
                <Trans>Strength:</Trans> {strengthLabel[strength] || <Trans>Very Weak</Trans>}
            </p>
        </div>
    );
}
