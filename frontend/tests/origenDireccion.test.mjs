import test from 'node:test'
import assert from 'node:assert/strict'
import { barrioLocalConfiable, direccionOrigenTrinidad } from '../src/utils/origenDireccion.js'

const barrios = [
  { nombre: 'Nueva Trinidad', tipo: 'Barrio', lat: -14.8202649, lng: -64.8739201 },
  { nombre: 'Villa Conchita', tipo: 'Barrio', lat: -14.81936137, lng: -64.8752224 },
  { nombre: 'Nueva Trinidad I', tipo: 'Barrio', lat: -14.820023, lng: -64.866054 }
]

test('una calle recibe el barrio verificado cercano sin perder la calle', () => {
  assert.equal(
    direccionOrigenTrinidad(
      { name: 'Calle 13', category: 'highway', address: { road: 'Calle 13' } },
      barrios,
      -14.8202649,
      -64.8739201
    ),
    'Calle 13, Nueva Trinidad, Trinidad'
  )
})

test('Nominatim tiene prioridad cuando confirma un barrio en la direccion', () => {
  assert.equal(
    direccionOrigenTrinidad(
      { name: 'Calle 13', category: 'highway', address: { road: 'Calle 13', neighbourhood: 'Nueva Trinidad' } },
      barrios,
      -14.8202649,
      -64.8739201
    ),
    'Calle 13, Nueva Trinidad, Trinidad'
  )
})

test('no inventa barrio cuando dos referencias estan demasiado cercanas', () => {
  const vecinos = [
    { nombre: 'Barrio A', tipo: 'Barrio', lat: -14.8200, lng: -64.8700 },
    { nombre: 'Barrio B', tipo: 'Barrio', lat: -14.8202, lng: -64.8700 }
  ]
  assert.equal(barrioLocalConfiable(vecinos, -14.8201, -64.8700), null)
})

test('no inventa barrio distante', () => {
  assert.equal(barrioLocalConfiable(barrios, -14.8308, -64.9024), null)
})

test('mantiene puntos de interes distintos de los nombres de carretera', () => {
  assert.equal(
    direccionOrigenTrinidad(
      { name: 'Mercado Local', category: 'amenity', address: { road: 'Calle 13' } },
      barrios,
      -14.8202649,
      -64.8739201
    ),
    'Mercado Local, Nueva Trinidad, Trinidad'
  )
})

test('una carretera sin datos adicionales no queda clasificada como establecimiento', () => {
  assert.equal(
    direccionOrigenTrinidad(
      { name: 'Calle 13', category: 'highway', address: { road: 'Calle 13' } },
      [],
      -14.8202649,
      -64.8739201
    ),
    'Calle 13, Trinidad'
  )
})
