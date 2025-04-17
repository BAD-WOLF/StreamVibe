import React from 'react'
import {Vibrant} from 'node-vibrant/browser'

export default function GetColor(){

    return (
        <>
          <main>
            <div className="w-full h-full flex flex-col gap-4">
                <div className="relative w-full h-full bg-cover bg-center p-4"
                    style={{ backgroundImage: `url(https://image.tmdb.org/t/p/w500${detail.backdrop_path || detail.poster_path})`,
                    }}>
                </div>
            </div>
                <div className="absolute inset-0 bg-black opacity-50"></div>
                    <div className="absolute inset-0 flex items-center justify-center text-white text-3xl font-bold">
                    {detail.title}
                </div>
         </main>
        </>
    )
}