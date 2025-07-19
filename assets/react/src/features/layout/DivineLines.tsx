import type {FC, ReactElement} from "react";
import {motion} from "framer-motion";

export const DivineLines: FC = (): ReactElement => {
    return (
        <div className="space-y-2 overflow-hidden relative">
            {Array.from({length: 3}).map<ReactElement>(
                (_: unknown, i: number): ReactElement => (
                    <motion.div
                        key={i}
                        className="h-1 w-full bg-gradient-to-r from-[#5B21B6] via-[#9B111E] to-[#0E7490]"
                        style={{
                            filter: "url(#psychedelicFilter)",
                            mixBlendMode: "screen",
                        }}
                        animate={{
                            x: ["-100%", "100%"],
                            opacity: [0.6, 1, 0.6],
                        }}
                        transition={{
                            x: {
                                duration: 6 + i * 1.2,
                                repeat: Infinity,
                                ease: "easeInOut",
                                repeatType: "reverse",
                            },
                            opacity: {
                                duration: 4 + i,
                                repeat: Infinity,
                                ease: "easeInOut",
                            },
                        }}
                    />
                )
            )}
            <svg style={{position: "absolute", width: 0, height: 0}}>
                <filter id="psychedelicFilter">
                    <feTurbulence
                        type="fractalNoise"
                        baseFrequency="0.01 0.3"
                        numOctaves="3"
                        result="turbulence"
                        seed="5"
                    />
                    <feDisplacementMap
                        in="SourceGraphic"
                        in2="turbulence"
                        scale="30"
                        xChannelSelector="R"
                        yChannelSelector="G"
                    />
                    <feColorMatrix type="hueRotate" values="0" result="hue">
                        <animate
                            attributeName="values"
                            from="0"
                            to="360"
                            dur="10s"
                            repeatCount="indefinite"
                        />
                    </feColorMatrix>
                </filter>
            </svg>
        </div>
    );
};
