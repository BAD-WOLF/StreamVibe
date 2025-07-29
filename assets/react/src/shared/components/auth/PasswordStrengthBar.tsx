// Visual component of the password strength bar

export default function PasswordStrengthBar({strength}: { strength: number; }) {
    const strengthLabel: Array<string> = ["Muito Fraca", "Fraca", "Média", "Boa", "Excelente"];
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
                Força: {strengthLabel[strength] || "Muito Fraca"}
            </p>
        </div>
    );
}
