// Component for the image with floating animation

import type {ReactElement} from "react";

export default function AnimatedLogo(): ReactElement {
    return (
        <div className="text-3xl font-extrabold text-white drop-shadow-lg tracking-wide animate-pulse">
            <img src="https://files.catbox.moe/txrqod.png"
                 alt="Ilustração"/>
        </div>
    );
}