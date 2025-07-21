import type {FC, ReactElement} from 'react';
import {useEffect, useState} from "react";
import {Header} from "@layout/Header";
import {Footer} from "@layout/Footer";
import {DivineLines} from "@layout/DivineLines";
import {FeaturedMovies} from "@content/home/components/FeaturedMovies.tsx";
import {CategoriesSection} from "@content/home/components/CategoriesSection";
import {getMovieList} from "@content/services/movieList.service";
import type {Movie} from "@content/types/movie";

export const Home: FC = (): ReactElement => {
    const [movies, setMovies] = useState<Movie[]>([]);

    useEffect((): void => {
        const list: Array<Movie> = getMovieList();
        setMovies(list);
    }, []);

    return (
        <div
            className="min-h-screen bg-gradient-to-br from-[#0E7490] via-[#1E3A5F] to-[#3D3D3D] text-white font-sans overflow-x-hidden">
            <Header/>
            <FeaturedMovies movies={movies}/>
            <DivineLines/>
            <CategoriesSection/>
            <Footer/>
        </div>
    );
};
