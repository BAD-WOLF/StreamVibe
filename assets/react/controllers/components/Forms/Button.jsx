import React from 'react'

const Button =({children})=>{
    return (
        <>
         <button className='flex justify-center items-center gap-1 text-gray-200 bg-ciano_escuro py-2 px-4 rounded-sm font-normal'>{children}</button>
        </>
    )
}

export default Button