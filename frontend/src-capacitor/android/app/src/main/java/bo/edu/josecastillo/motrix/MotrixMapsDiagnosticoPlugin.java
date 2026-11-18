package bo.edu.josecastillo.motrix;

import android.content.pm.ApplicationInfo;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.Signature;
import android.os.Build;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GoogleApiAvailability;

import java.security.MessageDigest;
import java.util.Locale;

/**
 * Diagnostico seguro para Google Maps en Android.
 * Nunca devuelve ni imprime la clave API: solo indica si existe.
 * Permite investigar errores sin conectar el telefono por USB.
 */
@CapacitorPlugin(name = "MotrixMapsDiagnostico")
public class MotrixMapsDiagnosticoPlugin extends Plugin {
    @PluginMethod
    public void estado(PluginCall call) {
        JSObject resultado = new JSObject();
        String paquete = getContext().getPackageName();
        resultado.put("paquete", paquete);

        try {
            PackageManager pm = getContext().getPackageManager();
            ApplicationInfo app = pm.getApplicationInfo(paquete, PackageManager.GET_META_DATA);
            String clave = app.metaData == null ? null :
                    app.metaData.getString("com.google.android.geo.API_KEY");
            resultado.put("claveConfigurada", clave != null && !clave.trim().isEmpty());
        } catch (Exception e) {
            resultado.put("claveConfigurada", false);
        }

        try {
            int codigo = GoogleApiAvailability.getInstance()
                    .isGooglePlayServicesAvailable(getContext());
            resultado.put("serviciosGoogle", codigo == ConnectionResult.SUCCESS
                    ? "Disponible" : "Error " + codigo);
        } catch (Exception e) {
            resultado.put("serviciosGoogle", "No se pudo comprobar");
        }

        try {
            PackageManager pm = getContext().getPackageManager();
            Signature[] firmas;
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                PackageInfo info = pm.getPackageInfo(
                        paquete, PackageManager.GET_SIGNING_CERTIFICATES);
                firmas = info.signingInfo == null ? null
                        : info.signingInfo.getApkContentsSigners();
            } else {
                @SuppressWarnings("deprecation")
                PackageInfo info = pm.getPackageInfo(paquete, PackageManager.GET_SIGNATURES);
                @SuppressWarnings("deprecation")
                Signature[] antiguas = info.signatures;
                firmas = antiguas;
            }
            if (firmas != null && firmas.length > 0) {
                byte[] digest = MessageDigest.getInstance("SHA-1")
                        .digest(firmas[0].toByteArray());
                StringBuilder huella = new StringBuilder();
                for (byte b : digest) {
                    if (huella.length() > 0) huella.append(':');
                    huella.append(String.format(Locale.US, "%02X", b & 0xff));
                }
                resultado.put("firmaSha1", huella.toString());
            } else {
                resultado.put("firmaSha1", "No disponible");
            }
        } catch (Exception e) {
            resultado.put("firmaSha1", "No disponible");
        }

        resultado.put("android", Build.VERSION.RELEASE);
        call.resolve(resultado);
    }
}
