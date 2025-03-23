import React from 'react'
import {Link} from 'react-router'

//import the component
import logo from '../../../../images/logo.png'

const Logo=()=>{
  return(
    <>
     <Link className='' to='/'>
        <img src={logo} alt="Carregando logo" style={{width:"180px"}}/>
    </Link>
    </>
  )
}

export default Logo