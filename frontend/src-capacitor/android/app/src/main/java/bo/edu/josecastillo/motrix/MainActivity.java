package bo.edu.josecastillo.motrix;

import android.graphics.Color;
import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // Google Maps SDK de Capacitor dibuja debajo del WebView.
        // Sin transparencia nativa, el mapa queda oculto en blanco.
        if (getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().setBackgroundColor(Color.TRANSPARENT);
        }
    }
}
