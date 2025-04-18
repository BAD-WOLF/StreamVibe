import React from "react";
import Footer from '../../components/Footer/Footer'
import Header from '../../components/Header/Header'
import { renderStars } from "./renderStars";
import { getColor } from "../../helper/getColor";


export default function Details({movieDetails}) {

    const [details, setDetails] = React.useState({});
    const [color, setColor] = React.useState(null);
    const [loading, setLoading] = React.useState(true);
  // Assuming movieDetails is an object with the movie's details
  React.useEffect(() => {
    
    // Check if movieDetails is not null or undefined
    if (movieDetails) {
      setDetails(movieDetails);
    }else{
      console.log("No movie details available.");
    }
  
}, [movieDetails])

    // React.useEffect(() => {
    //   console.log(details)
    // },[details])

    const fetchPalette=React.useCallback(async ()=>{

        const palette=await getColor(`/movies/image${details.backdrop_path}`)

         if(palette){
           setColor(palette.Vibrant.hex)
         }else{
           console.log("No palette available.")
         }

    },[])

    React.useEffect(() => {
      fetchPalette()
    },[fetchPalette])

    // React.useEffect(() => {
    //   if(color){
    //     console.log(color)
    //   }
    // },[color])
    
  return (
    <>
     <Header/>

    <main className="min-h-screen w-full flex flex-col pb-12">

      <section className="Container w-full h-full bg-center bg-cover p-8 rounded-4xl relative my-10" style={{backgroundImage: `url(https://image.tmdb.org/t/p/w500${details.backdrop_path || details.poster_path})`}}>

        <div className=" absolute inset-0 bg-gray-900/95 z-10 rounded-4xl"></div>
        <div className=" mx-auto flex justify-center items-center gap-8 h-full z-30">

        {/* Show Banner */}
        <div className="mb-4 z-30 ">
            <img 
                src={`https://image.tmdb.org/t/p/w500${details.poster_path || details.backdrop_path}`} 
                alt="loading the image" 
                className="w-full object-cover rounded-lg"
                style={{ width: "20rem" }}
            />
        </div>

            {/* Show Movie Details */}
            <div className="my-4 mt-8 font-light z-30 max-w-xl">
                <h1 className="text-2xl font-medium text-gray-200 mb-4">{details.title}</h1>

                <div className=" text-4xl flex items-center gap-1 mb-4">
                  <span className="flex gap-2">  {renderStars(details.vote_average)} </span>
                  <span className="text-sm text-gray-400 ml-2">({details.vote_average?.toFixed(1)} / 10)</span>
                </div>

              <div className="flex justify-start items-start gap-12 font-light mb-4">

                  <div className="flex flex-col gap-4">
                      <p className="flex flex-col ">
                        <span className="text-gray-300 text-lg">Data:</span>
                        <span className="text-sm text-gray-400">
                          📅 {new Date(details.release_date).toLocaleDateString('pt-BR')}
                        </span>
                      </p>

                      <p className="flex flex-col "> 
                        <span className="text-gray-300 text-lg">Duracao:</span>
                        <span className="text-sm text-gray-400"> ⏱ {details.runtime} min</span> 
                      </p>
                  </div>

                  <div className="flex flex-col gap-4">
                      <p className="flex flex-col ">
                        <span className="text-gray-300 text-lg">Popularidade:</span>
                        <span className="text-sm text-gray-400">📊 {details.popularity?.toFixed(1)}</span>
                      </p>

                      <p className="flex flex-col"> 
                        <span className="text-gray-300 text-lg">Genero:</span>
                        <span className="bg-gray-700 text-gray-200 text-xs px-2 py-1 rounded-full ">
                          {Array.isArray(details.genres) && (
                            details.genres.map((g)=>{
                            return(
                                <span key={g.id} className="">{g.name}</span>
                            )
                        }))}
                        </span>
                      </p>
                  </div>

                  <div className="flex flex-col gap-4">
                      <p className="flex flex-col ">
                        <span className="text-gray-300 text-lg">Titulo Original:</span>
                        <span className=" text-gray-400 text-sm ">{details.original_title}</span>
                      </p>

                      <p className="flex flex-col"> 
                        <span className="text-gray-300 text-lg">Idioma:</span>
                        <span className="bg-gray-700 text-gray-200 text-xs px-2 py-1 rounded-full">

                          {Array.isArray(details.spoken_languages) && (
                            details.spoken_languages.map((language)=>{
                              return(
                                <span key={language.id} className="">{language.name}</span>
                              )
                            }))}

                        </span>
                      </p>
                  </div>


              </div>

              <p className="text-gray-400 text-sm italic mb-4">{details.tagline}</p>                        
              <p className="text-lg text-gray-400 mb-2 flex flex-col justify-start items-start"> 
             
              <div className="">
                  <span className="text-lg text-gray-300">Descricao:</span> 
                  <span className="text-sm text-gray-400 text-justify">{details.overview}</span>
              </div>
              </p>

              <p className="text-lg text-gray-400 mb-2 flex flex-col justify-start items-start"> 
                <span className="text-lg text-gray-300">Orcamento:</span> 
                <span className="">{details.budget?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</span> 
              </p>

            </div>

        </div>

      </section>
    
      <section className="Container">

      </section>

    </main>

     <Footer/>
    </>
  );
}