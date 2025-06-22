import { type FC, type JSX } from "react";
import { motion } from "framer-motion";

export const Header: FC = (): JSX.Element => {
    const navItems: string[] = ["Home", "Trending", "Categories", "My List", "Login"];

    return (
        <header className="relative z-10 flex justify-between items-center px-8 py-6 backdrop-blur-sm">
            <motion.div
                whileHover={{ scale: 1.2, rotate: 360 }}
                transition={{ duration: 1 }}
                className="w-14 h-14 rounded-full bg-gradient-to-r from-[#5B21B6] to-[#9B111E] flex items-center justify-center shadow-2xl"
            >
                <span className="text-sm font-bold">SV</span>
            </motion.div>

            <nav role="Navigation" aria-label="Main Navigation" className="flex gap-6 text-sm font-medium">
                {navItems.map((item: string) => (
                    <motion.a
                        key={item}
                        whileTap={{ scale: 0.9 }}
                        className="hover:text-[#F0F4F8]"
                    >
                        {item}
                    </motion.a>
                ))}
            </nav>

            <motion.input
                type="text"
                placeholder="Search movies, shows..."
                className="bg-[#3D3D3D]/50 px-5 py-2 rounded-full backdrop-blur text-sm outline-none border border-[#5B21B6] placeholder-white"
                whileFocus={{ scale: 1.05 }}
                transition={{ duration: 0.3 }}
            />
        </header>
    );
};