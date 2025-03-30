import React from 'react'
import banner from '../../../../images/banner.jpg'
import {FaSearch} from 'react-icons/fa'

const Banner = () => {
 const [query, setQuery]=React.useState()

 function getSearch(e){
  e.preventDefault() // no loading on submit
    if(query){
      window.location.href=`/movies/show/${query}/1`
    }
 }
  return (
    <section className="relative bg-cover bg-center h-60 z-10 flex flex-col justify-center items-center" 
            style={{ backgroundImage: `url(${banner})`}}>

            <div className={`bg-color_gray_500/55 h-full w-full absolute top-0 left-0 z-0`}></div>
            <div className="flex flex-col justify-center items-center z-10 px-8 md:px-4">
                <div className=" text-gray-200 flex flex-col gap-4">
                    <div>    
                        <h1 className=" text-3xl font-normal md:text-5xl md:font-medium">Seja Bem Vindo<span className='text-ciano_escuro'>,</span></h1>
                        <p className="text-xl md:text-xl font-light">Aqui voce vai conhecer melhores Films, series e até mesmo Atores do momento </p>
                    </div>

                    {/* Form to submit movies, serie or actor to search */}
                    <form className='relative flex justify-center items-center' onSubmit={getSearch}>
                        <input type='search' placeholder='Pesquisar por films, series ou nome de um autor' className='w-full py-2 px-6 pr-8 text-lg text-gray-600 font-light placeholder:text-base placeholder:text-gray-400 placeholder:font-light rounded-full' value={query} onChange={(e)=>setQuery(e.target.value)}/>
                        
                        <span className='absolute right-2 flex justify-center items-center'>
                             <button className='text-xl font-light text-ciano_escuro'> <FaSearch/> </button>
                        </span>
                    </form>
                </div>
            </div>

    </section>
  )
}

export default Banner
