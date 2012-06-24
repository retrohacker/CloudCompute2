/*
 * SocketTest.cpp
 *
 *  Created on: Jun 22, 2012
 *      Author: crackers
 */

#include <iostream>
#include "WSMPI_Server.h"

using namespace std;

int main() {

	WSMPI_Server s("127.0.0.1",8080);
	s.start();
	/*
	while(true) {
		int resp = accept(MasterSocket,(sockaddr*)&node,&sin_size);
		if(resp==-1) {
			cout << "fail" << "\n";
		}
		else {
			cout << "WOAH!" << "\n";
		}
	}

	return close(MasterSocket);*/
}
