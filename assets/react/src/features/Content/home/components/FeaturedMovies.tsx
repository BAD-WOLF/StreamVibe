import type {FC, ReactElement} from "react";
import type {Movie} from "@content/types/movie";
import {MovieCard} from "./MovieCard";

interface FeaturedMoviesProps {
    movies: Movie[];
}

export const FeaturedMovies: FC<FeaturedMoviesProps> = ({movies}: FeaturedMoviesProps): ReactElement => (
    <section className="relative h-[90vh] flex items-center justify-center">
        <div className="relative z-10 w-full px-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            {movies.map((m: Movie): ReactElement => (
                <MovieCard key={m.title} movie={m}/>
            ))}
        </div>
    </section>
);
