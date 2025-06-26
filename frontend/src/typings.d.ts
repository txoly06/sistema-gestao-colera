/**
 * Declarações de tipos para o projeto
 * 
 * Este arquivo contém declarações de tipos para bibliotecas externas e 
 * componentes internos do sistema para resolver problemas de tipagem.
 */

// Namespace do Google Maps para o componente MapaGeral
declare namespace google {
  namespace maps {
    class Map {
      constructor(mapDiv: Element, options?: any);
      setCenter(latLng: any): void;
      setZoom(zoom: number): void;
    }
    
    class Marker {
      constructor(options?: any);
      setMap(map: Map | null): void;
      setPosition(latLng: any): void;
    }
    
    class LatLng {
      constructor(lat: number, lng: number);
    }

    class HeatmapLayer {
      constructor(options?: any);
      setMap(map: Map | null): void;
      setData(data: any): void;
    }

    class InfoWindow {
      constructor(options?: any);
      open(map?: Map, anchor?: Marker): void;
      setContent(content: string | Element): void;
    }
  }
}

// Declaração genérica para resolver erros de importação de módulos
// Esta abordagem permite que o TypeScript não acuse erros em imports de módulos que não têm declarações de tipo
declare module '*' {
  const component: React.ComponentType<any>;
  export default component;
}
