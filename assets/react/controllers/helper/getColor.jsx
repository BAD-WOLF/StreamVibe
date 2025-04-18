import React from 'react'
import {Vibrant} from 'node-vibrant/browser'


export async function getColor(imageUrl) {
    const vibrant = new Vibrant(imageUrl);

    try{
        if(vibrant){
            const palette = await vibrant.getPalette();
            return palette
        }
    }catch(err){
        console.error("Error fetching palette:", err);
        return null;
    }

}