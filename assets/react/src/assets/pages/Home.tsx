import { Component, type JSX } from "react";
import { DivineLines } from "../../components/shared/DivineLines";
import type { Movie } from "../../types/Movie";
import { Header } from "../../components/Home/Header";
import { CategoriesSection } from "../../components/Home/CategoriesSection";
import { FeaturedMovies } from "../../components/Home/FeaturedMovies";
import { Footer } from "../../components/Home/Footer";

export default class extends Component<{}, { movies: Movie[] }> {
    constructor(props: {}) {
        super(props);
        this.state = {
            movies: [
                {
                    title: "Nebula Rising",
                    description: "A cosmic tale of rebellion in the Andromeda system.",
                    poster: "https://image.tmdb.org/t/p/w500/nebula.jpg",
                    trailer: "https://www.w3schools.com/html/mov_bbb.mp4",
                },
                {
                    title: "Quantum Drift",
                    description: "An astronaut lost in the multiverse must find his way home.",
                    poster: "https://image.tmdb.org/t/p/w500/quantum.jpg",
                    trailer: "https://www.w3schools.com/html/movie.mp4",
                },
                {
                    title: "The Last Fractal",
                    description: "A hacker discovers the code that shapes reality itself.",
                    poster: "https://image.tmdb.org/t/p/w500/fractal.jpg",
                    trailer: "https://www.w3schools.com/html/mov_bbb.mp4",
                },
            ],
        };
    }

    render(): JSX.Element {
        return (
            <div
                className="min-h-screen bg-gradient-to-br from-[#0E7490] via-[#1E3A5F] to-[#3D3D3D] text-white font-sans overflow-x-hidden">

                {/* Header */}
                <Header />

                {/* Featured */}
                <FeaturedMovies movies={this.state.movies} />

                {/* Divider */}
                <DivineLines />

                {/* Categories */}
                <CategoriesSection />

                {/* Footer */}
                <Footer />

            </div>
        );
    }
}