import { FC } from "react";
import { motion } from "framer-motion";

export const Footer: FC = (): JSX.Element => {
    const links: string[] = ["Privacy", "Terms", "Support", "Contact", "License", "API"];

    return (
        <footer className="relative bg-[#0E7490]/10 py-16 px-6 border-t border-[#3D3D3D]/60 text-center">
            <motion.div
                animate={{ rotate: 360 }}
                transition={{ duration: 25, repeat: Infinity, ease: "linear" }}
                className="mx-auto w-16 h-16 mb-8 rounded-full border-4 border-[#9B111E] flex items-center justify-center shadow-[0_0_20px_#5B21B6]"
            >
                <span className="text-md font-extrabold tracking-widest text-white">SV</span>
            </motion.div>
            <div className="flex flex-wrap justify-center gap-10 text-sm text-[#F0F4F8]/70">
                {links.map((link: string) => (
                    <motion.a
                        key={link}
                        whileTap={{ scale: 0.95 }}
                        className="hover:text-white cursor-pointer"
                    >
                        {link}
                    </motion.a>
                ))}
            </div>
        </footer>
    );
};
