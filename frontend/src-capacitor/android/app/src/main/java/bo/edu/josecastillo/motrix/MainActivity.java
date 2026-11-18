package bo.edu.josecastillo.motrix;

import android.graphics.Color;
import android.graphics.drawable.ColorDrawable;
import android.os.Bundle;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Registrar antes de crear el bridge de Capacitor.
        registerPlugin(MotrixMapsDiagnosticoPlugin.class);
        super.onCreate(savedInstanceState);

        // Google Maps Android dibuja una vista nativa debajo del WebView.
        // Ni la ventana ni el WebView deben taparla con un fondo opaco.
        getWindow().setBackgroundDrawable(new ColorDrawable(Color.TRANSPARENT));
        getWindow().getDecorView().setBackgroundColor(Color.TRANSPARENT);
        if (getBridge() != null && getBridge().getWebView() != null) {
            getBridge().getWebView().setBackgroundColor(Color.TRANSPARENT);
        }
    }
}
