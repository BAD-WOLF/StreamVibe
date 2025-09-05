import {motion} from "framer-motion";
import {type FC, type ReactElement, useEffect, useState} from "react";
import { t } from 'i18next';

export const Header: FC = (): ReactElement => {
    const [scrolled, setScrolled] = useState(false);
    const navItems: string[] = [t("Home"), t("Trending"), t("Categories"), t("My List")];
    const projectLogoUrl = "https://files.catbox.moe/txrqod.png";
    // Placeholder for user profile initials or image
    const userInitials = "SV";

    useEffect((): () => void => {
        const handleScroll: () => void = (): void => {
            setScrolled(window.scrollY > 50);
        };
        window.addEventListener("scroll", handleScroll);
        return (): void => window.removeEventListener("scroll", handleScroll);
    }, []);

    return (
        <header
            className={`fixed top-0 w-full z-50 px-8 py-4 flex justify-between items-center transition-all duration-500 ${
                scrolled
                    ? "bg-[#141414]/80 backdrop-blur-lg shadow-lg"
                    : "bg-transparent"
            }`}
        >
            {/* Project Logo */}
            <div className="flex items-center">
                <img
                    src={projectLogoUrl}
                    alt={t("Project Logo")}
                    className="h-10 w-auto object-contain"
                />
            </div>

            {/* Navigation */}
            <nav className="flex gap-8 text-sm font-medium">
                {navItems.map((item: string): ReactElement => (
                    <button
                        key={item}
                        className="hover:text-white text-gray-300 transition-colors"
                    >
                        {item}
                    </button>
                ))}
            </nav>

            <div className="flex items-center gap-6">
                {/* Search Input */}
                <motion.input
                    type="text"
                    placeholder={t("Search movies, shows...")}
                    className="bg-[#3D3D3D]/50 px-5 py-2 rounded-full backdrop-blur text-sm outline-none border border-[#5B21B6] placeholder-white"
                    whileFocus={{scale: 1.05}}
                    transition={{duration: 0.3}}
                />

                {/* User Profile Circle */}
                <motion.div
                    whileHover={{scale: 1.1, rotate: 360}}
                    transition={{duration: 0.8}}
                    className="w-12 h-12 rounded-full bg-gradient-to-r from-[#5B21B6] to-[#9B111E] flex items-center justify-center shadow-xl"
                >
                    <span className="text-sm font-bold text-white">{userInitials}</span>
                </motion.div>
            </div>
        </header>
    );
};