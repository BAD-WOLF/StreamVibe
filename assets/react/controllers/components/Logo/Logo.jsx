import React from 'react'
import {Link} from 'react-router'
import logo from '../../../../images/logo.png'

const Logo=()=>{
  return(
    <>
     <Link className='' to='/'>
        <img src={logo} alt="Carregando logo" className={`w-48`}/>
    </Link>
    </>
  )
}

export default Logo